<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProjectRequest;
use App\Http\Requests\UpdateProjectRequest;
use App\Http\Resources\ProjectResource;
use App\Http\Resources\UserResource;
use App\Services\ProjectService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class ProjectController extends Controller
{
    use ApiResponse;

    public function __construct(
        private ProjectService $projectService
    ) {}

    #[OA\Get(
        path: '/projects',
        summary: 'List projects',
        description: 'Get a paginated list of projects the user has access to',
        tags: ['Projects'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'status', in: 'query', description: 'Filter by status', schema: new OA\Schema(type: 'string', enum: ['active', 'on_hold', 'completed', 'archived'])),
            new OA\Parameter(name: 'visibility', in: 'query', description: 'Filter by visibility', schema: new OA\Schema(type: 'string', enum: ['public', 'private'])),
            new OA\Parameter(name: 'search', in: 'query', description: 'Search by name or description', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'include_archived', in: 'query', description: 'Include archived projects', schema: new OA\Schema(type: 'boolean')),
            new OA\Parameter(name: 'sort_by', in: 'query', description: 'Sort field', schema: new OA\Schema(type: 'string', enum: ['name', 'created_at', 'updated_at'])),
            new OA\Parameter(name: 'sort_direction', in: 'query', description: 'Sort direction', schema: new OA\Schema(type: 'string', enum: ['asc', 'desc'])),
            new OA\Parameter(name: 'per_page', in: 'query', description: 'Items per page (max 100)', schema: new OA\Schema(type: 'integer', default: 20)),
            new OA\Parameter(name: 'page', in: 'query', description: 'Page number', schema: new OA\Schema(type: 'integer', default: 1))
        ],
        responses: [
            new OA\Response(response: 200, description: 'Projects retrieved successfully'),
            new OA\Response(response: 401, description: 'Unauthorized')
        ]
    )]
    public function index(Request $request): JsonResponse
    {
        $filters = $request->only([
            'status',
            'visibility',
            'manager_id',
            'search',
            'include_archived',
            'sort_by',
            'sort_direction',
        ]);

        $perPage = min($request->get('per_page', 20), 100);

        $projects = $this->projectService->getProjects(
            $request->attributes->get('auth_org_id'),
            $request->attributes->get('auth_user_id'),
            $request->attributes->get('auth_role'),
            $filters,
            $perPage
        );

        return $this->paginatedResponse(
            $projects->through(fn ($project) => new ProjectResource($project)),
            'Projects retrieved successfully'
        );
    }

    #[OA\Post(
        path: '/projects',
        summary: 'Create project',
        description: 'Create a new project (requires manager or admin role)',
        tags: ['Projects'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name'],
                properties: [
                    new OA\Property(property: 'name', type: 'string', example: 'New Project'),
                    new OA\Property(property: 'description', type: 'string', example: 'Project description'),
                    new OA\Property(property: 'status', type: 'string', enum: ['active', 'on_hold', 'completed'], example: 'active'),
                    new OA\Property(property: 'visibility', type: 'string', enum: ['public', 'private'], example: 'public'),
                    new OA\Property(property: 'start_date', type: 'string', format: 'date', example: '2024-01-01'),
                    new OA\Property(property: 'end_date', type: 'string', format: 'date', example: '2024-12-31')
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Project created successfully'),
            new OA\Response(response: 401, description: 'Unauthorized'),
            new OA\Response(response: 403, description: 'Forbidden'),
            new OA\Response(response: 422, description: 'Validation error')
        ]
    )]
    public function store(StoreProjectRequest $request): JsonResponse
    {
        $project = $this->projectService->createProject(
            $request->attributes->get('auth_org_id'),
            $request->attributes->get('auth_user_id'),
            $request->validated()
        );

        return $this->createdResponse(
            new ProjectResource($project),
            'Project created successfully'
        );
    }

    #[OA\Get(
        path: '/projects/{id}',
        summary: 'Get project',
        description: 'Get a specific project by ID',
        tags: ['Projects'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, description: 'Project ID', schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(response: 200, description: 'Project retrieved successfully'),
            new OA\Response(response: 401, description: 'Unauthorized'),
            new OA\Response(response: 404, description: 'Project not found')
        ]
    )]
    public function show(Request $request, int $id): JsonResponse
    {
        $project = $this->projectService->getProject(
            $request->attributes->get('auth_org_id'),
            $request->attributes->get('auth_user_id'),
            $request->attributes->get('auth_role'),
            $id
        );

        if (!$project) {
            return $this->notFoundResponse('Project not found or access denied');
        }

        // Calculate progress
        $project->progress_percentage = $project->tasks_count > 0
            ? round(($project->completed_tasks_count / $project->tasks_count) * 100, 2)
            : 0;

        return $this->successResponse(
            new ProjectResource($project),
            'Project retrieved successfully'
        );
    }

    #[OA\Put(
        path: '/projects/{id}',
        summary: 'Update project',
        description: 'Update an existing project',
        tags: ['Projects'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, description: 'Project ID', schema: new OA\Schema(type: 'integer'))
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'name', type: 'string'),
                    new OA\Property(property: 'description', type: 'string'),
                    new OA\Property(property: 'status', type: 'string', enum: ['active', 'on_hold', 'completed']),
                    new OA\Property(property: 'visibility', type: 'string', enum: ['public', 'private']),
                    new OA\Property(property: 'start_date', type: 'string', format: 'date'),
                    new OA\Property(property: 'end_date', type: 'string', format: 'date')
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Project updated successfully'),
            new OA\Response(response: 401, description: 'Unauthorized'),
            new OA\Response(response: 404, description: 'Project not found'),
            new OA\Response(response: 422, description: 'Validation error')
        ]
    )]
    public function update(UpdateProjectRequest $request, int $id): JsonResponse
    {
        $project = $this->projectService->updateProject(
            $request->attributes->get('auth_org_id'),
            $request->attributes->get('auth_user_id'),
            $id,
            $request->validated()
        );

        if (!$project) {
            return $this->notFoundResponse('Project not found');
        }

        return $this->successResponse(
            new ProjectResource($project),
            'Project updated successfully'
        );
    }

    #[OA\Delete(
        path: '/projects/{id}',
        summary: 'Archive project',
        description: 'Soft delete (archive) a project',
        tags: ['Projects'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, description: 'Project ID', schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(response: 200, description: 'Project archived successfully'),
            new OA\Response(response: 401, description: 'Unauthorized'),
            new OA\Response(response: 403, description: 'Forbidden'),
            new OA\Response(response: 404, description: 'Project not found')
        ]
    )]
    public function destroy(Request $request, int $id): JsonResponse
    {
        $role = $request->attributes->get('auth_role');

        if (!$this->projectService->canManageProject($role)) {
            return $this->forbiddenResponse('Only managers and admins can delete projects');
        }

        $deleted = $this->projectService->deleteProject(
            $request->attributes->get('auth_org_id'),
            $request->attributes->get('auth_user_id'),
            $id
        );

        if (!$deleted) {
            return $this->notFoundResponse('Project not found');
        }

        return $this->successResponse(null, 'Project archived successfully');
    }

    #[OA\Post(
        path: '/projects/{id}/restore',
        summary: 'Restore project',
        description: 'Restore an archived project',
        tags: ['Projects'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, description: 'Project ID', schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(response: 200, description: 'Project restored successfully'),
            new OA\Response(response: 401, description: 'Unauthorized'),
            new OA\Response(response: 403, description: 'Forbidden'),
            new OA\Response(response: 404, description: 'Project not found')
        ]
    )]
    public function restore(Request $request, int $id): JsonResponse
    {
        $role = $request->attributes->get('auth_role');

        if (!$this->projectService->canManageProject($role)) {
            return $this->forbiddenResponse('Only managers and admins can restore projects');
        }

        $project = $this->projectService->restoreProject(
            $request->attributes->get('auth_org_id'),
            $request->attributes->get('auth_user_id'),
            $id
        );

        if (!$project) {
            return $this->notFoundResponse('Project not found or not archived');
        }

        return $this->successResponse(
            new ProjectResource($project),
            'Project restored successfully'
        );
    }

    #[OA\Post(
        path: '/projects/{id}/members',
        summary: 'Add project member',
        description: 'Add a user to a project',
        tags: ['Projects'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, description: 'Project ID', schema: new OA\Schema(type: 'integer'))
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['user_id'],
                properties: [
                    new OA\Property(property: 'user_id', type: 'integer', example: 2)
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Member added successfully'),
            new OA\Response(response: 401, description: 'Unauthorized'),
            new OA\Response(response: 403, description: 'Forbidden'),
            new OA\Response(response: 404, description: 'Project or user not found')
        ]
    )]
    public function addMember(Request $request, int $id): JsonResponse
    {
        $role = $request->attributes->get('auth_role');

        if (!$this->projectService->canManageProject($role)) {
            return $this->forbiddenResponse('Only managers and admins can add members to projects');
        }

        $request->validate([
            'user_id' => 'required|integer|exists:users,id',
        ]);

        $result = $this->projectService->addMember(
            $request->attributes->get('auth_org_id'),
            $request->attributes->get('auth_user_id'),
            $id,
            $request->user_id
        );

        if (!$result['success']) {
            $statusCode = $result['code'] === 'NOT_FOUND' ? 404 : 400;
            return $this->errorResponse($result['error'], $result['code'], $statusCode);
        }

        return $this->successResponse(
            new ProjectResource($result['project']),
            'Member added successfully'
        );
    }

    #[OA\Delete(
        path: '/projects/{id}/members/{memberId}',
        summary: 'Remove project member',
        description: 'Remove a user from a project',
        tags: ['Projects'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, description: 'Project ID', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'memberId', in: 'path', required: true, description: 'Member user ID', schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(response: 200, description: 'Member removed successfully'),
            new OA\Response(response: 401, description: 'Unauthorized'),
            new OA\Response(response: 403, description: 'Forbidden'),
            new OA\Response(response: 404, description: 'Project or member not found')
        ]
    )]
    public function removeMember(Request $request, int $id, int $memberId): JsonResponse
    {
        $role = $request->attributes->get('auth_role');

        if (!$this->projectService->canManageProject($role)) {
            return $this->forbiddenResponse('Only managers and admins can remove members from projects');
        }

        $result = $this->projectService->removeMember(
            $request->attributes->get('auth_org_id'),
            $request->attributes->get('auth_user_id'),
            $id,
            $memberId
        );

        if (!$result['success']) {
            $statusCode = $result['code'] === 'NOT_FOUND' ? 404 : 400;
            return $this->errorResponse($result['error'], $result['code'], $statusCode);
        }

        return $this->successResponse(
            new ProjectResource($result['project']),
            'Member removed successfully'
        );
    }

    #[OA\Get(
        path: '/projects/{id}/members',
        summary: 'List project members',
        description: 'Get all members of a project',
        tags: ['Projects'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, description: 'Project ID', schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(response: 200, description: 'Members retrieved successfully'),
            new OA\Response(response: 401, description: 'Unauthorized'),
            new OA\Response(response: 404, description: 'Project not found')
        ]
    )]
    public function members(Request $request, int $id): JsonResponse
    {
        $members = $this->projectService->getMembers(
            $request->attributes->get('auth_org_id'),
            $id
        );

        if ($members === null) {
            return $this->notFoundResponse('Project not found');
        }

        return $this->successResponse(
            UserResource::collection($members),
            'Project members retrieved successfully'
        );
    }
}
