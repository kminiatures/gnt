<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Task;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rule;

class TaskController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Task::with(['project', 'assignedUser', 'parent']);

        // フィルタリング
        if ($request->has('project_id')) {
            $query->byProject($request->project_id);
        }

        if ($request->has('status')) {
            $query->byStatus($request->status);
        }

        if ($request->has('assigned_to')) {
            $query->assignedTo($request->assigned_to);
        }

        if ($request->boolean('root_only')) {
            $query->rootTasks();
        }

        // ソート
        $sortBy = $request->get('sort_by', 'sort_order');
        $sortOrder = $request->get('sort_order', 'asc');
        $query->orderBy($sortBy, $sortOrder);

        $tasks = $query->paginate($request->get('per_page', 50));

        return response()->json($tasks);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'project_id' => 'required|exists:projects,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'duration' => 'nullable|integer|min:1',
            'progress' => 'nullable|integer|min:0|max:100',
            'status' => ['nullable', Rule::in(['not_started', 'in_progress', 'completed', 'on_hold', 'cancelled'])],
            'sort_order' => 'nullable|integer',
            'parent_id' => 'nullable|exists:tasks,id',
            'predecessor' => 'nullable|string',
            'assigned_to' => 'nullable|exists:users,id',
        ]);

        // 親タスクが同じプロジェクト内か確認
        if ($validated['parent_id']) {
            $parentTask = Task::find($validated['parent_id']);
            if ($parentTask->project_id !== $validated['project_id']) {
                return response()->json(['error' => 'Parent task must be in the same project'], 422);
            }
        }

        // sort_orderが指定されていない場合、プロジェクト内での最大値+1を設定
        if (!isset($validated['sort_order'])) {
            $maxSortOrder = Task::where('project_id', $validated['project_id'])
                              ->where('parent_id', $validated['parent_id'] ?? null)
                              ->max('sort_order') ?? 0;
            $validated['sort_order'] = $maxSortOrder + 1;
        }

        $task = Task::create($validated);
        $task->load(['project', 'assignedUser', 'parent', 'children']);
        
        // 親タスクの日付を自動更新
        $this->updateParentTaskDates($task);

        return response()->json($task, 201);
    }

    public function show(Task $task): JsonResponse
    {
        $task->load(['project', 'assignedUser', 'parent', 'children', 'predecessorTasks']);
        return response()->json($task);
    }

    public function update(Request $request, Task $task): JsonResponse
    {
        \Log::info('Task update request received', [
            'task_id' => $task->id,
            'request_data' => $request->all(),
            'current_parent_id' => $task->parent_id
        ]);
        
        try {
            $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'start_date' => 'sometimes|required|date',
            'end_date' => 'sometimes|required|date|after_or_equal:start_date',
            'duration' => 'nullable|integer|min:1',
            'progress' => 'nullable|integer|min:0|max:100',
            'status' => ['nullable', Rule::in(['not_started', 'in_progress', 'completed', 'on_hold', 'cancelled'])],
            'sort_order' => 'nullable|numeric',
            'parent_id' => 'nullable|exists:tasks,id',
            'predecessor' => 'nullable|string',
            'assigned_to' => 'nullable|exists:users,id',
        ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            \Log::error('Task update validation failed', [
                'task_id' => $task->id,
                'errors' => $e->errors(),
                'request_data' => $request->all()
            ]);
            throw $e;
        }

        // 親タスクが同じプロジェクト内か確認
        if (isset($validated['parent_id']) && $validated['parent_id']) {
            $parentTask = Task::find($validated['parent_id']);
            if ($parentTask->project_id !== $task->project_id) {
                return response()->json(['error' => 'Parent task must be in the same project'], 422);
            }
        }

        // 自分自身を親に設定しようとしていないか確認
        if (isset($validated['parent_id']) && $validated['parent_id'] == $task->id) {
            return response()->json(['error' => 'Task cannot be its own parent'], 422);
        }

        // 親の変更があるかチェック
        $oldParentId = $task->parent_id;
        
        $task->update($validated);
        $task->load(['project', 'assignedUser', 'parent', 'children']);
        
        \Log::info('Task updated successfully', [
            'task_id' => $task->id,
            'old_parent_id' => $oldParentId,
            'new_parent_id' => $task->parent_id,
            'validated_data' => $validated
        ]);
        
        // 新しい親タスクの日付を更新
        $this->updateParentTaskDates($task);
        
        // 元の親タスクの日付も更新（親が変更された場合）
        if ($oldParentId && $oldParentId !== $task->parent_id) {
            $oldParent = Task::find($oldParentId);
            if ($oldParent) {
                $this->updateParentTaskDates($oldParent);
            }
        }

        return response()->json($task);
    }

    public function destroy(Task $task): JsonResponse
    {
        // 削除前に親タスクのIDを保存
        $parentId = $task->parent_id;
        
        $task->delete();
        
        // 親タスクの日付を更新（削除されたタスクの親がある場合）
        if ($parentId) {
            $parentTask = Task::find($parentId);
            if ($parentTask) {
                $this->updateParentTaskDates($parentTask);
            }
        }
        
        return response()->json(['message' => 'Task deleted successfully']);
    }

    public function ganttData(Request $request): JsonResponse
    {
        $projectId = $request->get('project_id');
        
        if (!$projectId) {
            return response()->json(['error' => 'project_id is required'], 400);
        }

        $tasks = Task::with(['children.children.children'])
                    ->byProject($projectId)
                    ->rootTasks()
                    ->get();

        $ganttData = $tasks->map(fn($task) => $task->toGanttFormat())->toArray();

        return response()->json($ganttData);
    }

    public function updateProgress(Request $request, Task $task): JsonResponse
    {
        $validated = $request->validate([
            'progress' => 'required|integer|min:0|max:100',
        ]);

        $task->update(['progress' => $validated['progress']]);

        return response()->json($task);
    }

    public function updateDates(Request $request, Task $task): JsonResponse
    {
        $validated = $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        // 期間も計算して更新
        $startDate = \Carbon\Carbon::parse($validated['start_date']);
        $endDate = \Carbon\Carbon::parse($validated['end_date']);
        $validated['duration'] = $startDate->diffInDays($endDate) + 1;

        $task->update($validated);
        
        // 親タスクの日付を自動更新し、更新された親タスクを収集
        $updatedParents = $this->updateParentTaskDates($task);

        return response()->json([
            'task' => $task,
            'updated_parents' => $updatedParents
        ]);
    }
    
    /**
     * 親タスクの日付を子タスクの日付範囲に基づいて更新
     */
    private function updateParentTaskDates(Task $task): array
    {
        $updatedParents = [];
        
        if (!$task->parent_id) {
            return $updatedParents; // 親タスクがない場合は空配列を返す
        }
        
        $parentTask = Task::find($task->parent_id);
        if (!$parentTask) {
            return $updatedParents; // 親タスクが見つからない場合は空配列を返す
        }
        
        // 親タスクの全子タスクを取得（孫タスクも含む）
        $allChildTasks = $this->getAllDescendantTasks($parentTask);
        
        if ($allChildTasks->isEmpty()) {
            return $updatedParents; // 子タスクがない場合は空配列を返す
        }
        
        // 子タスクの最小開始日と最大終了日を計算
        $minStartDate = $allChildTasks->min('start_date');
        $maxEndDate = $allChildTasks->max('end_date');
        
        // 親タスクの日付を更新
        $updateData = [];
        if ($minStartDate && $minStartDate !== $parentTask->start_date) {
            $updateData['start_date'] = $minStartDate;
        }
        if ($maxEndDate && $maxEndDate !== $parentTask->end_date) {
            $updateData['end_date'] = $maxEndDate;
        }
        
        if (!empty($updateData)) {
            // 期間も再計算
            if (isset($updateData['start_date']) && isset($updateData['end_date'])) {
                $startDate = \Carbon\Carbon::parse($updateData['start_date']);
                $endDate = \Carbon\Carbon::parse($updateData['end_date']);
                $updateData['duration'] = $startDate->diffInDays($endDate) + 1;
            }
            
            $parentTask->update($updateData);
            $parentTask->refresh(); // 最新データを取得
            $updatedParents[] = $parentTask;
            
            // 再帰的に祖父母タスクも更新
            $grandParentUpdates = $this->updateParentTaskDates($parentTask);
            $updatedParents = array_merge($updatedParents, $grandParentUpdates);
        }
        
        return $updatedParents;
    }
    
    /**
     * タスクの全子孫タスクを取得（再帰的）
     */
    private function getAllDescendantTasks(Task $task)
    {
        $descendants = collect();
        
        // 直接の子タスクを取得
        $children = Task::where('parent_id', $task->id)->get();
        
        foreach ($children as $child) {
            $descendants->push($child);
            // 再帰的に孫タスクも取得
            $descendants = $descendants->merge($this->getAllDescendantTasks($child));
        }
        
        return $descendants;
    }
}