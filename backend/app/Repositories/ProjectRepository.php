<?php

namespace App\Repositories;

use App\Models\Project;
use App\Models\ProjectMember;
use App\Repositories\Interfaces\ProjectRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class ProjectRepository extends BaseRepository implements ProjectRepositoryInterface
{
    public function __construct(Project $model)
    {
        parent::__construct($model);
    }

    public function findByOrganization(int $organizationId, int $projectId): ?Project
    {
        return $this->model
            ->where('organization_id', $organizationId)
            ->find($projectId);
    }

    public function getByOrganization(
        int $organizationId,
        array $filters = [],
        int $perPage = 20
    ): LengthAwarePaginator {
        $query = $this->model
            ->where('organization_id', $organizationId)
            ->with(['manager', 'members'])
            ->withCount([
                'tasks',
                'tasks as completed_tasks_count' => fn ($q) => $q->where('status', 'done')
            ]);

        return $this->applyFilters($query, $filters)->paginate($perPage);
    }

    public function getAccessibleProjects(
        int $organizationId,
        int $userId,
        string $role,
        array $filters = [],
        int $perPage = 20
    ): LengthAwarePaginator {
        $query = $this->model
            ->where('organization_id', $organizationId)
            ->with(['manager', 'members'])
            ->withCount([
                'tasks',
                'tasks as completed_tasks_count' => fn ($q) => $q->where('status', 'done')
            ]);

        // Members can only see public projects or projects they're members of
        if ($role === 'member') {
            $query->where(function ($q) use ($userId) {
                $q->where('visibility', 'public')
                    ->orWhereHas('members', fn ($m) => $m->where('user_id', $userId));
            });
        }

        return $this->applyFilters($query, $filters)->paginate($perPage);
    }

    public function findWithDetails(int $id): ?Project
    {
        return $this->model
            ->with(['manager', 'creator', 'members'])
            ->withCount([
                'tasks',
                'tasks as completed_tasks_count' => fn ($q) => $q->where('status', 'done')
            ])
            ->find($id);
    }

    public function addMember(int $projectId, int $userId): bool
    {
        if ($this->isMember($projectId, $userId)) {
            return false;
        }

        ProjectMember::create([
            'project_id' => $projectId,
            'user_id' => $userId,
        ]);

        return true;
    }

    public function removeMember(int $projectId, int $userId): bool
    {
        return ProjectMember::where('project_id', $projectId)
            ->where('user_id', $userId)
            ->delete() > 0;
    }

    public function isMember(int $projectId, int $userId): bool
    {
        return ProjectMember::where('project_id', $projectId)
            ->where('user_id', $userId)
            ->exists();
    }

    public function getMembers(int $projectId): Collection
    {
        $project = $this->find($projectId);

        if (!$project) {
            return new Collection();
        }

        return $project->members;
    }

    public function restore(int $id): bool
    {
        $project = $this->model->withTrashed()->find($id);

        if ($project && $project->trashed()) {
            return $project->restore();
        }

        return false;
    }

    public function findTrashed(int $organizationId, int $projectId): ?Project
    {
        return $this->model
            ->withTrashed()
            ->where('organization_id', $organizationId)
            ->find($projectId);
    }

    protected function applyFilters($query, array $filters)
    {
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['visibility'])) {
            $query->where('visibility', $filters['visibility']);
        }

        if (!empty($filters['manager_id'])) {
            $query->where('manager_id', $filters['manager_id']);
        }

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if (!empty($filters['include_archived'])) {
            $query->withTrashed();
        }

        // Sorting
        $sortField = $filters['sort_by'] ?? 'created_at';
        $sortDirection = $filters['sort_direction'] ?? 'desc';
        $allowedSortFields = ['title', 'status', 'start_date', 'end_date', 'created_at', 'updated_at'];

        if (in_array($sortField, $allowedSortFields)) {
            $query->orderBy($sortField, $sortDirection === 'asc' ? 'asc' : 'desc');
        }

        return $query;
    }
}
