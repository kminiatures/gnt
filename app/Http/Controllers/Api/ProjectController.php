<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class ProjectController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Project::with(['owner', 'rootTasks']);

        // フィルタリング
        if ($request->has('status')) {
            $query->byStatus($request->status);
        }

        if ($request->has('owner_id')) {
            $query->byOwner($request->owner_id);
        }

        // ソート
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        $projects = $query->paginate($request->get('per_page', 15));

        return response()->json($projects);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'status' => ['nullable', Rule::in(['planning', 'active', 'completed', 'on_hold', 'cancelled'])],
            'progress' => 'nullable|integer|min:0|max:100',
            'owner_id' => 'required|exists:users,id',
        ]);

        $project = Project::create($validated);
        $project->load(['owner', 'rootTasks']);

        return response()->json($project, 201);
    }

    public function show(Project $project): JsonResponse
    {
        $project->load(['owner', 'tasks.assignedUser', 'tasks.children']);
        return response()->json($project);
    }

    public function update(Request $request, Project $project): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'status' => ['nullable', Rule::in(['planning', 'active', 'completed', 'on_hold', 'cancelled'])],
            'progress' => 'nullable|integer|min:0|max:100',
            'owner_id' => 'sometimes|required|exists:users,id',
        ]);

        $project->update($validated);
        $project->load(['owner', 'rootTasks']);

        return response()->json($project);
    }

    public function destroy(Project $project): JsonResponse
    {
        $project->delete();
        return response()->json(['message' => 'Project deleted successfully']);
    }

    public function ganttData(Project $project): JsonResponse
    {
        $tasks = $project->rootTasks()->with(['children.children.children'])->get();
        $ganttData = $tasks->map(fn($task) => $task->toGanttFormat())->toArray();

        return response()->json($ganttData);
    }

    /**
     * プロジェクト一覧ページを表示
     */
    public function indexPage(Request $request): Response
    {
        $query = Project::with(['owner', 'rootTasks']);

        // フィルタリング
        if ($request->has('status')) {
            $query->byStatus($request->status);
        }

        if ($request->has('owner_id')) {
            $query->byOwner($request->owner_id);
        }

        // ソート
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        $projects = $query->get();

        return Inertia::render('Projects/Index', [
            'projects' => $projects,
        ]);
    }
}