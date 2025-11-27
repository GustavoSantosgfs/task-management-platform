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

class ProjectController extends Controller
{
    use ApiResponse;

    public function __construct(
        private ProjectService $projectService
    ) {}

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

        $projects->through(function ($project) {
            $project->progress_percentage = $project->tasks_count > 0
                ? round(($project->completed_tasks_count / $project->tasks_count) * 100, 2)
                : 0;
            return $project;
        });

        return $this->paginatedResponse(
            $projects->through(fn ($project) => new ProjectResource($project)),
            'Projects retrieved successfully'
        );
    }

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
