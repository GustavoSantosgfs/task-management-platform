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
use OpenApi\Attributes as OA;

class TaskController extends Controller
{
    use ApiResponse;

    public function __construct(
        private TaskService $taskService
    ) {}

    #[OA\Get(
        path: '/projects/{projectId}/tasks',
        summary: 'List tasks',
        description: 'Get a paginated list of tasks for a project',
        tags: ['Tasks'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'projectId', in: 'path', required: true, description: 'Project ID', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'status', in: 'query', description: 'Filter by status', schema: new OA\Schema(type: 'string', enum: ['backlog', 'in_progress', 'review', 'completed'])),
            new OA\Parameter(name: 'priority', in: 'query', description: 'Filter by priority', schema: new OA\Schema(type: 'string', enum: ['low', 'medium', 'high', 'critical'])),
            new OA\Parameter(name: 'assignee_id', in: 'query', description: 'Filter by assignee', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'search', in: 'query', description: 'Search by title', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'due_date_from', in: 'query', description: 'Due date from', schema: new OA\Schema(type: 'string', format: 'date')),
            new OA\Parameter(name: 'due_date_to', in: 'query', description: 'Due date to', schema: new OA\Schema(type: 'string', format: 'date')),
            new OA\Parameter(name: 'per_page', in: 'query', description: 'Items per page', schema: new OA\Schema(type: 'integer', default: 20))
        ],
        responses: [
            new OA\Response(response: 200, description: 'Tasks retrieved successfully'),
            new OA\Response(response: 401, description: 'Unauthorized'),
            new OA\Response(response: 404, description: 'Project not found')
        ]
    )]
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

    #[OA\Post(
        path: '/projects/{projectId}/tasks',
        summary: 'Create task',
        description: 'Create a new task in a project',
        tags: ['Tasks'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'projectId', in: 'path', required: true, description: 'Project ID', schema: new OA\Schema(type: 'integer'))
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['title'],
                properties: [
                    new OA\Property(property: 'title', type: 'string', example: 'New Task'),
                    new OA\Property(property: 'description', type: 'string'),
                    new OA\Property(property: 'status', type: 'string', enum: ['backlog', 'in_progress', 'review', 'completed']),
                    new OA\Property(property: 'priority', type: 'string', enum: ['low', 'medium', 'high', 'critical']),
                    new OA\Property(property: 'assignee_id', type: 'integer'),
                    new OA\Property(property: 'due_date', type: 'string', format: 'date'),
                    new OA\Property(property: 'estimated_hours', type: 'number')
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Task created successfully'),
            new OA\Response(response: 401, description: 'Unauthorized'),
            new OA\Response(response: 404, description: 'Project not found'),
            new OA\Response(response: 422, description: 'Validation error')
        ]
    )]
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

    #[OA\Get(
        path: '/projects/{projectId}/tasks/{taskId}',
        summary: 'Get task',
        description: 'Get a specific task',
        tags: ['Tasks'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'projectId', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'taskId', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(response: 200, description: 'Task retrieved successfully'),
            new OA\Response(response: 401, description: 'Unauthorized'),
            new OA\Response(response: 404, description: 'Task not found')
        ]
    )]
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

    #[OA\Put(
        path: '/projects/{projectId}/tasks/{taskId}',
        summary: 'Update task',
        description: 'Update an existing task',
        tags: ['Tasks'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'projectId', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'taskId', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'title', type: 'string'),
                    new OA\Property(property: 'description', type: 'string'),
                    new OA\Property(property: 'status', type: 'string', enum: ['backlog', 'in_progress', 'review', 'completed']),
                    new OA\Property(property: 'priority', type: 'string', enum: ['low', 'medium', 'high', 'critical']),
                    new OA\Property(property: 'assignee_id', type: 'integer'),
                    new OA\Property(property: 'due_date', type: 'string', format: 'date'),
                    new OA\Property(property: 'estimated_hours', type: 'number')
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Task updated successfully'),
            new OA\Response(response: 401, description: 'Unauthorized'),
            new OA\Response(response: 404, description: 'Task not found'),
            new OA\Response(response: 422, description: 'Validation error')
        ]
    )]
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

    #[OA\Delete(
        path: '/projects/{projectId}/tasks/{taskId}',
        summary: 'Delete task',
        description: 'Delete a task',
        tags: ['Tasks'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'projectId', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'taskId', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(response: 200, description: 'Task deleted successfully'),
            new OA\Response(response: 401, description: 'Unauthorized'),
            new OA\Response(response: 404, description: 'Task not found')
        ]
    )]
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

    #[OA\Post(
        path: '/projects/{projectId}/tasks/{taskId}/restore',
        summary: 'Restore task',
        description: 'Restore a deleted task',
        tags: ['Tasks'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'projectId', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'taskId', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(response: 200, description: 'Task restored successfully'),
            new OA\Response(response: 401, description: 'Unauthorized'),
            new OA\Response(response: 404, description: 'Task not found')
        ]
    )]
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

    #[OA\Get(
        path: '/my-tasks',
        summary: 'Get my tasks',
        description: 'Get tasks assigned to the current user',
        tags: ['Tasks'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'status', in: 'query', description: 'Filter by status', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'priority', in: 'query', description: 'Filter by priority', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'project_id', in: 'query', description: 'Filter by project', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'per_page', in: 'query', description: 'Items per page', schema: new OA\Schema(type: 'integer', default: 20))
        ],
        responses: [
            new OA\Response(response: 200, description: 'Tasks retrieved successfully'),
            new OA\Response(response: 401, description: 'Unauthorized')
        ]
    )]
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

    #[OA\Get(
        path: '/projects/{projectId}/tasks/{taskId}/dependencies',
        summary: 'List task dependencies',
        description: 'Get all dependencies for a task',
        tags: ['Tasks'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'projectId', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'taskId', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(response: 200, description: 'Dependencies retrieved successfully'),
            new OA\Response(response: 401, description: 'Unauthorized'),
            new OA\Response(response: 404, description: 'Task not found')
        ]
    )]
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

    #[OA\Post(
        path: '/projects/{projectId}/tasks/{taskId}/dependencies',
        summary: 'Add task dependency',
        description: 'Add a dependency to a task',
        tags: ['Tasks'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'projectId', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'taskId', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['depends_on_task_id'],
                properties: [
                    new OA\Property(property: 'depends_on_task_id', type: 'integer', example: 2)
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Dependency added successfully'),
            new OA\Response(response: 401, description: 'Unauthorized'),
            new OA\Response(response: 404, description: 'Task not found')
        ]
    )]
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

    #[OA\Delete(
        path: '/projects/{projectId}/tasks/{taskId}/dependencies/{dependencyId}',
        summary: 'Remove task dependency',
        description: 'Remove a dependency from a task',
        tags: ['Tasks'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'projectId', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'taskId', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'dependencyId', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(response: 200, description: 'Dependency removed successfully'),
            new OA\Response(response: 401, description: 'Unauthorized'),
            new OA\Response(response: 404, description: 'Task or dependency not found')
        ]
    )]
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

    #[OA\Get(
        path: '/projects/{projectId}/tasks/{taskId}/comments',
        summary: 'List task comments',
        description: 'Get all comments for a task',
        tags: ['Tasks'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'projectId', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'taskId', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(response: 200, description: 'Comments retrieved successfully'),
            new OA\Response(response: 401, description: 'Unauthorized'),
            new OA\Response(response: 404, description: 'Task not found')
        ]
    )]
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

    #[OA\Post(
        path: '/projects/{projectId}/tasks/{taskId}/comments',
        summary: 'Add task comment',
        description: 'Add a comment to a task',
        tags: ['Tasks'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'projectId', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'taskId', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['content'],
                properties: [
                    new OA\Property(property: 'content', type: 'string', example: 'This is a comment'),
                    new OA\Property(property: 'mentions', type: 'array', items: new OA\Items(type: 'integer'))
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Comment added successfully'),
            new OA\Response(response: 401, description: 'Unauthorized'),
            new OA\Response(response: 404, description: 'Task not found')
        ]
    )]
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

    #[OA\Put(
        path: '/projects/{projectId}/tasks/{taskId}/comments/{commentId}',
        summary: 'Update task comment',
        description: 'Update an existing comment',
        tags: ['Tasks'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'projectId', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'taskId', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'commentId', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['content'],
                properties: [
                    new OA\Property(property: 'content', type: 'string'),
                    new OA\Property(property: 'mentions', type: 'array', items: new OA\Items(type: 'integer'))
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Comment updated successfully'),
            new OA\Response(response: 401, description: 'Unauthorized'),
            new OA\Response(response: 403, description: 'Forbidden'),
            new OA\Response(response: 404, description: 'Comment not found')
        ]
    )]
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

    #[OA\Delete(
        path: '/projects/{projectId}/tasks/{taskId}/comments/{commentId}',
        summary: 'Delete task comment',
        description: 'Delete a comment',
        tags: ['Tasks'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'projectId', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'taskId', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'commentId', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(response: 200, description: 'Comment deleted successfully'),
            new OA\Response(response: 401, description: 'Unauthorized'),
            new OA\Response(response: 403, description: 'Forbidden'),
            new OA\Response(response: 404, description: 'Comment not found')
        ]
    )]
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
