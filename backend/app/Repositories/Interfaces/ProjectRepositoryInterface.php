<?php

namespace App\Repositories\Interfaces;

use App\Models\Project;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

interface ProjectRepositoryInterface extends RepositoryInterface
{
    public function findByOrganization(int $organizationId, int $projectId): ?Project;

    public function getByOrganization(
        int $organizationId,
        array $filters = [],
        int $perPage = 20
    ): LengthAwarePaginator;

    public function getAccessibleProjects(
        int $organizationId,
        int $userId,
        string $role,
        array $filters = [],
        int $perPage = 20
    ): LengthAwarePaginator;

    public function findWithDetails(int $id): ?Project;

    public function addMember(int $projectId, int $userId): bool;

    public function removeMember(int $projectId, int $userId): bool;

    public function isMember(int $projectId, int $userId): bool;

    public function getMembers(int $projectId): Collection;

    public function restore(int $id): bool;
}
