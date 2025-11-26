<?php

namespace App\Repositories;

use App\Models\Task;
use App\Models\TaskComment;
use App\Repositories\Interfaces\TaskRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class TaskRepository extends BaseRepository implements TaskRepositoryInterface
{
    public function __construct(Task $model)
    {
        parent::__construct($model);
    }

    public function findByProject(int $projectId, int $taskId): ?Task
    {
        return $this->model
            ->where('project_id', $projectId)
            ->where('id', $taskId)
            ->first();
    }

    public function getByProject(
        int $projectId,
        array $filters = [],
        int $perPage = 20
    ): LengthAwarePaginator {
        $query = $this->model
            ->where('project_id', $projectId)
            ->with(['assignee', 'creator']);

        // Apply filters
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['priority'])) {
            $query->where('priority', $filters['priority']);
        }

        if (!empty($filters['assignee_id'])) {
            $query->where('assignee_id', $filters['assignee_id']);
        }

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if (!empty($filters['due_date_from'])) {
            $query->where('due_date', '>=', $filters['due_date_from']);
        }

        if (!empty($filters['due_date_to'])) {
            $query->where('due_date', '<=', $filters['due_date_to']);
        }

        if (isset($filters['include_archived']) && $filters['include_archived']) {
            $query->withTrashed();
        }

        // Sorting
        $sortBy = $filters['sort_by'] ?? 'position';
        $sortDirection = $filters['sort_direction'] ?? 'asc';
        $allowedSorts = ['title', 'status', 'priority', 'due_date', 'position', 'created_at'];

        if (in_array($sortBy, $allowedSorts)) {
            $query->orderBy($sortBy, $sortDirection);
        }

        return $query->paginate($perPage);
    }

    public function findWithDetails(int $id): ?Task
    {
        return $this->model
            ->with(['project', 'assignee', 'creator', 'updater', 'comments.user', 'dependencies'])
            ->withCount('comments')
            ->find($id);
    }

    public function getTasksByAssignee(int $userId, array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        $query = $this->model
            ->where('assignee_id', $userId)
            ->with(['project', 'creator']);

        // Apply filters
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['priority'])) {
            $query->where('priority', $filters['priority']);
        }

        if (!empty($filters['project_id'])) {
            $query->where('project_id', $filters['project_id']);
        }

        // Sorting
        $sortBy = $filters['sort_by'] ?? 'due_date';
        $sortDirection = $filters['sort_direction'] ?? 'asc';

        $query->orderBy($sortBy, $sortDirection);

        return $query->paginate($perPage);
    }

    public function addDependency(int $taskId, int $dependsOnTaskId): bool
    {
        $task = $this->find($taskId);
        if (!$task) {
            return false;
        }

        $task->dependencies()->syncWithoutDetaching([$dependsOnTaskId]);
        return true;
    }

    public function removeDependency(int $taskId, int $dependsOnTaskId): bool
    {
        $task = $this->find($taskId);
        if (!$task) {
            return false;
        }

        $task->dependencies()->detach($dependsOnTaskId);
        return true;
    }

    public function getDependencies(int $taskId): Collection
    {
        $task = $this->find($taskId);
        if (!$task) {
            return new Collection();
        }

        return $task->dependencies;
    }

    public function getDependents(int $taskId): Collection
    {
        $task = $this->find($taskId);
        if (!$task) {
            return new Collection();
        }

        return $task->dependents;
    }

    public function getComments(int $taskId): Collection
    {
        return TaskComment::where('task_id', $taskId)
            ->with('user')
            ->orderBy('created_at', 'asc')
            ->get();
    }

    public function addComment(int $taskId, int $userId, string $content, ?array $mentions = null): TaskComment
    {
        return TaskComment::create([
            'task_id' => $taskId,
            'user_id' => $userId,
            'content' => $content,
            'mentions' => $mentions,
        ]);
    }

    public function updateComment(int $commentId, string $content, ?array $mentions = null): ?TaskComment
    {
        $comment = TaskComment::find($commentId);
        if (!$comment) {
            return null;
        }

        $comment->update([
            'content' => $content,
            'mentions' => $mentions,
        ]);

        return $comment->fresh();
    }

    public function deleteComment(int $commentId): bool
    {
        $comment = TaskComment::find($commentId);
        if (!$comment) {
            return false;
        }

        return $comment->delete();
    }

    public function findComment(int $commentId): ?TaskComment
    {
        return TaskComment::with('user')->find($commentId);
    }

    public function restore(int $id): bool
    {
        $task = $this->model->withTrashed()->find($id);
        if (!$task) {
            return false;
        }

        return $task->restore();
    }

    public function updatePosition(int $taskId, int $position): bool
    {
        $task = $this->find($taskId);
        if (!$task) {
            return false;
        }

        return $task->update(['position' => $position]);
    }

    public function getMaxPosition(int $projectId): int
    {
        return $this->model
            ->where('project_id', $projectId)
            ->max('position') ?? 0;
    }

    public function findTrashed(int $projectId, int $taskId): ?Task
    {
        return $this->model
            ->withTrashed()
            ->where('project_id', $projectId)
            ->where('id', $taskId)
            ->first();
    }
}
