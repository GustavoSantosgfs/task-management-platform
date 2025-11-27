<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTaskRequest;
use App\Http\Requests\UpdateTaskRequest;
use App\Http\Resources\TaskResource;
use App\Http\Resources\TaskCommentResource;
use App\Services\TaskService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TaskController extends Controller
{
    use ApiResponse;

    public function __construct(
        private TaskService $taskService
    ) {}

    public function index(Request $request, int $projectId): JsonResponse
    {
        $filters = $request->only([
            'status',
            'priority',
            'assignee_id',
            'search',
            'due_date_from',
            'due_date_to',
            'include_archived',
            'sort_by',
            'sort_direction',
        ]);

        $perPage = min($request->get('per_page', 20), 100);

        $tasks = $this->taskService->getTasks(
            $request->attributes->get('auth_org_id'),
            $projectId,
            $request->attributes->get('auth_user_id'),
            $request->attributes->get('auth_role'),
            $filters,
            $perPage
        );

        if ($tasks === null) {
            return $this->notFoundResponse('Project not found or access denied');
        }

        return $this->paginatedResponse(
            $tasks->through(fn ($task) => new TaskResource($task)),
            'Tasks retrieved successfully'
        );
    }

    public function store(StoreTaskRequest $request, int $projectId): JsonResponse
    {
        $task = $this->taskService->createTask(
            $request->attributes->get('auth_org_id'),
            $projectId,
            $request->attributes->get('auth_user_id'),
            $request->validated()
        );

        if (!$task) {
            return $this->notFoundResponse('Project not found');
        }

        return $this->createdResponse(
            new TaskResource($task),
            'Task created successfully'
        );
    }

    public function show(Request $request, int $projectId, int $taskId): JsonResponse
    {
        $task = $this->taskService->getTask(
            $request->attributes->get('auth_org_id'),
            $projectId,
            $request->attributes->get('auth_user_id'),
            $request->attributes->get('auth_role'),
            $taskId
        );

        if (!$task) {
            return $this->notFoundResponse('Task not found or access denied');
        }

        return $this->successResponse(
            new TaskResource($task),
            'Task retrieved successfully'
        );
    }

    public function update(UpdateTaskRequest $request, int $projectId, int $taskId): JsonResponse
    {
        $task = $this->taskService->updateTask(
            $request->attributes->get('auth_org_id'),
            $projectId,
            $request->attributes->get('auth_user_id'),
            $taskId,
            $request->validated()
        );

        if (!$task) {
            return $this->notFoundResponse('Task not found');
        }

        return $this->successResponse(
            new TaskResource($task),
            'Task updated successfully'
        );
    }

    public function destroy(Request $request, int $projectId, int $taskId): JsonResponse
    {
        $deleted = $this->taskService->deleteTask(
            $request->attributes->get('auth_org_id'),
            $projectId,
            $request->attributes->get('auth_user_id'),
            $taskId
        );

        if (!$deleted) {
            return $this->notFoundResponse('Task not found');
        }

        return $this->successResponse(null, 'Task deleted successfully');
    }

    public function restore(Request $request, int $projectId, int $taskId): JsonResponse
    {
        $task = $this->taskService->restoreTask(
            $request->attributes->get('auth_org_id'),
            $projectId,
            $request->attributes->get('auth_user_id'),
            $taskId
        );

        if (!$task) {
            return $this->notFoundResponse('Task not found or not deleted');
        }

        return $this->successResponse(
            new TaskResource($task),
            'Task restored successfully'
        );
    }

    public function myTasks(Request $request): JsonResponse
    {
        $filters = $request->only([
            'status',
            'priority',
            'project_id',
            'sort_by',
            'sort_direction',
        ]);

        $perPage = min($request->get('per_page', 20), 100);

        $tasks = $this->taskService->getMyTasks(
            $request->attributes->get('auth_user_id'),
            $filters,
            $perPage
        );

        return $this->paginatedResponse(
            $tasks->through(fn ($task) => new TaskResource($task)),
            'My tasks retrieved successfully'
        );
    }

    // Dependencies

    public function dependencies(Request $request, int $projectId, int $taskId): JsonResponse
    {
        $dependencies = $this->taskService->getDependencies($projectId, $taskId);

        if ($dependencies === null) {
            return $this->notFoundResponse('Task not found');
        }

        return $this->successResponse(
            TaskResource::collection($dependencies),
            'Task dependencies retrieved successfully'
        );
    }

    public function addDependency(Request $request, int $projectId, int $taskId): JsonResponse
    {
        $request->validate([
            'depends_on_task_id' => 'required|integer|exists:tasks,id',
        ]);

        $result = $this->taskService->addDependency(
            $request->attributes->get('auth_org_id'),
            $projectId,
            $request->attributes->get('auth_user_id'),
            $taskId,
            $request->depends_on_task_id
        );

        if (!$result['success']) {
            $statusCode = match ($result['code']) {
                'NOT_FOUND' => 404,
                'FORBIDDEN' => 403,
                default => 400,
            };
            return $this->errorResponse($result['error'], $result['code'], $statusCode);
        }

        return $this->successResponse(
            new TaskResource($result['task']),
            'Dependency added successfully'
        );
    }

    public function removeDependency(Request $request, int $projectId, int $taskId, int $dependencyId): JsonResponse
    {
        $result = $this->taskService->removeDependency(
            $request->attributes->get('auth_org_id'),
            $projectId,
            $request->attributes->get('auth_user_id'),
            $taskId,
            $dependencyId
        );

        if (!$result['success']) {
            $statusCode = $result['code'] === 'NOT_FOUND' ? 404 : 400;
            return $this->errorResponse($result['error'], $result['code'], $statusCode);
        }

        return $this->successResponse(
            new TaskResource($result['task']),
            'Dependency removed successfully'
        );
    }

    // Comments

    public function comments(Request $request, int $projectId, int $taskId): JsonResponse
    {
        $comments = $this->taskService->getComments($projectId, $taskId);

        if ($comments === null) {
            return $this->notFoundResponse('Task not found');
        }

        return $this->successResponse(
            TaskCommentResource::collection($comments),
            'Comments retrieved successfully'
        );
    }

    public function addComment(Request $request, int $projectId, int $taskId): JsonResponse
    {
        $request->validate([
            'content' => 'required|string|max:5000',
            'mentions' => 'nullable|array',
            'mentions.*' => 'integer|exists:users,id',
        ]);

        $result = $this->taskService->addComment(
            $request->attributes->get('auth_org_id'),
            $projectId,
            $request->attributes->get('auth_user_id'),
            $taskId,
            $request->content,
            $request->mentions
        );

        if (!$result['success']) {
            $statusCode = $result['code'] === 'NOT_FOUND' ? 404 : 400;
            return $this->errorResponse($result['error'], $result['code'], $statusCode);
        }

        return $this->createdResponse(
            new TaskCommentResource($result['comment']),
            'Comment added successfully'
        );
    }

    public function updateComment(Request $request, int $projectId, int $taskId, int $commentId): JsonResponse
    {
        $request->validate([
            'content' => 'required|string|max:5000',
            'mentions' => 'nullable|array',
            'mentions.*' => 'integer|exists:users,id',
        ]);

        $result = $this->taskService->updateComment(
            $request->attributes->get('auth_org_id'),
            $projectId,
            $request->attributes->get('auth_user_id'),
            $taskId,
            $commentId,
            $request->content,
            $request->mentions
        );

        if (!$result['success']) {
            $statusCode = match ($result['code']) {
                'NOT_FOUND' => 404,
                'FORBIDDEN' => 403,
                default => 400,
            };
            return $this->errorResponse($result['error'], $result['code'], $statusCode);
        }

        return $this->successResponse(
            new TaskCommentResource($result['comment']),
            'Comment updated successfully'
        );
    }

    public function deleteComment(Request $request, int $projectId, int $taskId, int $commentId): JsonResponse
    {
        $result = $this->taskService->deleteComment(
            $request->attributes->get('auth_org_id'),
            $projectId,
            $request->attributes->get('auth_user_id'),
            $request->attributes->get('auth_role'),
            $taskId,
            $commentId
        );

        if (!$result['success']) {
            $statusCode = match ($result['code']) {
                'NOT_FOUND' => 404,
                'FORBIDDEN' => 403,
                default => 400,
            };
            return $this->errorResponse($result['error'], $result['code'], $statusCode);
        }

        return $this->successResponse(null, 'Comment deleted successfully');
    }
}
