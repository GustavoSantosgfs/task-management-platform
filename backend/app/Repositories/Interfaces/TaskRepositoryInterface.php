<?php

namespace App\Repositories\Interfaces;

use App\Models\Task;
use App\Models\TaskComment;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

interface TaskRepositoryInterface extends RepositoryInterface
{
    public function findByProject(int $projectId, int $taskId): ?Task;

    public function getByProject(
        int $projectId,
        array $filters = [],
        int $perPage = 20
    ): LengthAwarePaginator;

    public function findWithDetails(int $id): ?Task;

    public function getTasksByAssignee(int $userId, array $filters = [], int $perPage = 20): LengthAwarePaginator;

    public function addDependency(int $taskId, int $dependsOnTaskId): bool;

    public function removeDependency(int $taskId, int $dependsOnTaskId): bool;

    public function getDependencies(int $taskId): Collection;

    public function getDependents(int $taskId): Collection;

    public function getComments(int $taskId): Collection;

    public function addComment(int $taskId, int $userId, string $content, ?array $mentions = null): TaskComment;

    public function updateComment(int $commentId, string $content, ?array $mentions = null): ?TaskComment;

    public function deleteComment(int $commentId): bool;

    public function findComment(int $commentId): ?TaskComment;

    public function restore(int $id): bool;

    public function updatePosition(int $taskId, int $position): bool;

    public function getMaxPosition(int $projectId): int;
}
