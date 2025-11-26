<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\OrganizationUser;
use App\Models\Project;
use App\Repositories\Interfaces\ProjectRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class ProjectService
{
    public function __construct(
        private ProjectRepositoryInterface $projectRepository
    ) {}

    public function getProjects(
        int $organizationId,
        int $userId,
        string $role,
        array $filters = [],
        int $perPage = 20
    ): LengthAwarePaginator {
        return $this->projectRepository->getAccessibleProjects(
            $organizationId,
            $userId,
            $role,
            $filters,
            $perPage
        );
    }

    public function getProject(
        int $organizationId,
        int $userId,
        string $role,
        int $projectId
    ): ?Project {
        $project = $this->projectRepository->findByOrganization($organizationId, $projectId);

        if (!$project) {
            return null;
        }

        // Check access for private projects
        if (!$this->canAccessProject($project, $userId, $role)) {
            return null;
        }

        // Load details
        return $this->projectRepository->findWithDetails($projectId);
    }

    public function createProject(
        int $organizationId,
        int $userId,
        array $data
    ): Project {
        $projectData = [
            'organization_id' => $organizationId,
            'created_by' => $userId,
            'manager_id' => $data['manager_id'] ?? $userId,
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'start_date' => $data['start_date'] ?? null,
            'end_date' => $data['end_date'] ?? null,
            'status' => $data['status'] ?? 'planning',
            'visibility' => $data['visibility'] ?? 'public',
        ];

        $project = $this->projectRepository->create($projectData);

        // Add creator as project member
        $this->projectRepository->addMember($project->id, $userId);

        // Add manager as project member if different from creator
        if (isset($data['manager_id']) && $data['manager_id'] != $userId) {
            $this->projectRepository->addMember($project->id, $data['manager_id']);
        }

        // Log activity
        $this->logActivity('created', $project, $userId, $organizationId, "Project '{$project->title}' was created");

        return $project->load(['manager', 'members']);
    }

    public function updateProject(
        int $organizationId,
        int $userId,
        int $projectId,
        array $data
    ): ?Project {
        $project = $this->projectRepository->findByOrganization($organizationId, $projectId);

        if (!$project) {
            return null;
        }

        $oldValues = $project->toArray();

        $updateData = array_filter([
            'title' => $data['title'] ?? null,
            'description' => $data['description'] ?? null,
            'start_date' => $data['start_date'] ?? null,
            'end_date' => $data['end_date'] ?? null,
            'status' => $data['status'] ?? null,
            'visibility' => $data['visibility'] ?? null,
            'manager_id' => $data['manager_id'] ?? null,
        ], fn ($value) => $value !== null);

        $project = $this->projectRepository->update($projectId, $updateData);

        // Log activity
        $this->logActivity(
            'updated',
            $project,
            $userId,
            $organizationId,
            "Project '{$project->title}' was updated",
            $oldValues,
            $project->toArray()
        );

        return $project->load(['manager', 'members']);
    }

    public function deleteProject(
        int $organizationId,
        int $userId,
        int $projectId
    ): bool {
        $project = $this->projectRepository->findByOrganization($organizationId, $projectId);

        if (!$project) {
            return false;
        }

        // Log activity before deletion
        $this->logActivity('archived', $project, $userId, $organizationId, "Project '{$project->title}' was archived");

        return $this->projectRepository->delete($projectId);
    }

    public function restoreProject(
        int $organizationId,
        int $userId,
        int $projectId
    ): ?Project {
        $project = $this->projectRepository->findTrashed($organizationId, $projectId);

        if (!$project || !$project->trashed()) {
            return null;
        }

        $this->projectRepository->restore($projectId);

        // Log activity
        $this->logActivity('restored', $project, $userId, $organizationId, "Project '{$project->title}' was restored");

        return $project->fresh()->load(['manager', 'members']);
    }

    public function addMember(
        int $organizationId,
        int $userId,
        int $projectId,
        int $memberId
    ): array {
        $project = $this->projectRepository->findByOrganization($organizationId, $projectId);

        if (!$project) {
            return ['success' => false, 'error' => 'Project not found', 'code' => 'NOT_FOUND'];
        }

        // Check if user is in the same organization
        $memberOrgUser = OrganizationUser::where('organization_id', $organizationId)
            ->where('user_id', $memberId)
            ->first();

        if (!$memberOrgUser) {
            return ['success' => false, 'error' => 'User is not a member of this organization', 'code' => 'INVALID_USER'];
        }

        // Check if already a member
        if ($this->projectRepository->isMember($projectId, $memberId)) {
            return ['success' => false, 'error' => 'User is already a member of this project', 'code' => 'ALREADY_MEMBER'];
        }

        $this->projectRepository->addMember($projectId, $memberId);

        // Log activity
        $this->logActivity('member_added', $project, $userId, $organizationId, "Member was added to project '{$project->title}'");

        return ['success' => true, 'project' => $project->load('members')];
    }

    public function removeMember(
        int $organizationId,
        int $userId,
        int $projectId,
        int $memberId
    ): array {
        $project = $this->projectRepository->findByOrganization($organizationId, $projectId);

        if (!$project) {
            return ['success' => false, 'error' => 'Project not found', 'code' => 'NOT_FOUND'];
        }

        // Cannot remove the project manager
        if ($project->manager_id === $memberId) {
            return ['success' => false, 'error' => 'Cannot remove the project manager. Assign a new manager first.', 'code' => 'CANNOT_REMOVE_MANAGER'];
        }

        if (!$this->projectRepository->isMember($projectId, $memberId)) {
            return ['success' => false, 'error' => 'Member not found in this project', 'code' => 'NOT_FOUND'];
        }

        $this->projectRepository->removeMember($projectId, $memberId);

        // Log activity
        $this->logActivity('member_removed', $project, $userId, $organizationId, "Member was removed from project '{$project->title}'");

        return ['success' => true, 'project' => $project->load('members')];
    }

    public function getMembers(int $organizationId, int $projectId): ?Collection
    {
        $project = $this->projectRepository->findByOrganization($organizationId, $projectId);

        if (!$project) {
            return null;
        }

        return $this->projectRepository->getMembers($projectId);
    }

    public function canAccessProject(Project $project, int $userId, string $role): bool
    {
        // Admins and managers can access all projects
        if (in_array($role, ['admin', 'project_manager'])) {
            return true;
        }

        // Public projects are accessible to all org members
        if ($project->visibility === 'public') {
            return true;
        }

        // Private projects require membership
        return $this->projectRepository->isMember($project->id, $userId);
    }

    public function canManageProject(string $role): bool
    {
        return in_array($role, ['admin', 'project_manager']);
    }

    private function logActivity(
        string $action,
        Project $project,
        int $userId,
        int $organizationId,
        string $description,
        ?array $oldValues = null,
        ?array $newValues = null
    ): void {
        ActivityLog::log($action, $project, $userId, $organizationId, $description, $oldValues, $newValues);
    }
}
