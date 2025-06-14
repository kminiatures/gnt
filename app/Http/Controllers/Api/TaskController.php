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

        return response()->json($task, 201);
    }

    public function show(Task $task): JsonResponse
    {
        $task->load(['project', 'assignedUser', 'parent', 'children', 'predecessorTasks']);
        return response()->json($task);
    }

    public function update(Request $request, Task $task): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'start_date' => 'sometimes|required|date',
            'end_date' => 'sometimes|required|date|after_or_equal:start_date',
            'duration' => 'nullable|integer|min:1',
            'progress' => 'nullable|integer|min:0|max:100',
            'status' => ['nullable', Rule::in(['not_started', 'in_progress', 'completed', 'on_hold', 'cancelled'])],
            'sort_order' => 'nullable|integer',
            'parent_id' => 'nullable|exists:tasks,id',
            'predecessor' => 'nullable|string',
            'assigned_to' => 'nullable|exists:users,id',
        ]);

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

        $task->update($validated);
        $task->load(['project', 'assignedUser', 'parent', 'children']);

        return response()->json($task);
    }

    public function destroy(Task $task): JsonResponse
    {
        $task->delete();
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

        $task->update($validated);

        return response()->json($task);
    }
}