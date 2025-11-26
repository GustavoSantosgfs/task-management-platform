<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\Task;
use App\Models\TaskComment;
use App\Repositories\Interfaces\ProjectRepositoryInterface;
use App\Repositories\Interfaces\TaskRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class TaskService
{
    public function __construct(
        private TaskRepositoryInterface $taskRepository,
        private ProjectRepositoryInterface $projectRepository
    ) {}

    public function getTasks(
        int $organizationId,
        int $projectId,
        int $userId,
        string $role,
        array $filters = [],
        int $perPage = 20
    ): ?LengthAwarePaginator {
        // First check if project exists and user has access
        $project = $this->projectRepository->findByOrganization($organizationId, $projectId);

        if (!$project) {
            return null;
        }

        // Check access for private projects
        if ($project->visibility === 'private' && !in_array($role, ['admin', 'project_manager'])) {
            if (!$this->projectRepository->isMember($projectId, $userId)) {
                return null;
            }
        }

        return $this->taskRepository->getByProject($projectId, $filters, $perPage);
    }

    public function getTask(
        int $organizationId,
        int $projectId,
        int $userId,
        string $role,
        int $taskId
    ): ?Task {
        // First check if project exists and user has access
        $project = $this->projectRepository->findByOrganization($organizationId, $projectId);

        if (!$project) {
            return null;
        }

        // Check access for private projects
        if ($project->visibility === 'private' && !in_array($role, ['admin', 'project_manager'])) {
            if (!$this->projectRepository->isMember($projectId, $userId)) {
                return null;
            }
        }

        return $this->taskRepository->findWithDetails($taskId);
    }

    public function getMyTasks(
        int $userId,
        array $filters = [],
        int $perPage = 20
    ): LengthAwarePaginator {
        return $this->taskRepository->getTasksByAssignee($userId, $filters, $perPage);
    }

    public function createTask(
        int $organizationId,
        int $projectId,
        int $userId,
        array $data
    ): ?Task {
        // Check if project exists
        $project = $this->projectRepository->findByOrganization($organizationId, $projectId);

        if (!$project) {
            return null;
        }

        // Get next position
        $maxPosition = $this->taskRepository->getMaxPosition($projectId);

        $taskData = [
            'project_id' => $projectId,
            'created_by' => $userId,
            'updated_by' => $userId,
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'assignee_id' => $data['assignee_id'] ?? null,
            'priority' => $data['priority'] ?? 'medium',
            'status' => $data['status'] ?? 'todo',
            'due_date' => $data['due_date'] ?? null,
            'due_date_timezone' => $data['due_date_timezone'] ?? 'UTC',
            'position' => $data['position'] ?? ($maxPosition + 1),
        ];

        $task = $this->taskRepository->create($taskData);

        // Log activity
        $this->logActivity('created', $task, $userId, $organizationId, "Task '{$task->title}' was created");

        return $task->load(['assignee', 'creator', 'project']);
    }

    public function updateTask(
        int $organizationId,
        int $projectId,
        int $userId,
        int $taskId,
        array $data
    ): ?Task {
        $project = $this->projectRepository->findByOrganization($organizationId, $projectId);

        if (!$project) {
            return null;
        }

        $task = $this->taskRepository->findByProject($projectId, $taskId);

        if (!$task) {
            return null;
        }

        $oldValues = $task->toArray();

        $updateData = array_filter([
            'title' => $data['title'] ?? null,
            'description' => $data['description'] ?? null,
            'assignee_id' => $data['assignee_id'] ?? null,
            'priority' => $data['priority'] ?? null,
            'status' => $data['status'] ?? null,
            'due_date' => $data['due_date'] ?? null,
            'due_date_timezone' => $data['due_date_timezone'] ?? null,
            'position' => $data['position'] ?? null,
            'updated_by' => $userId,
        ], fn ($value) => $value !== null);

        $task = $this->taskRepository->update($taskId, $updateData);

        // Log activity
        $this->logActivity(
            'updated',
            $task,
            $userId,
            $organizationId,
            "Task '{$task->title}' was updated",
            $oldValues,
            $task->toArray()
        );

        return $task->load(['assignee', 'creator', 'project']);
    }

    public function deleteTask(
        int $organizationId,
        int $projectId,
        int $userId,
        int $taskId
    ): bool {
        $project = $this->projectRepository->findByOrganization($organizationId, $projectId);

        if (!$project) {
            return false;
        }

        $task = $this->taskRepository->findByProject($projectId, $taskId);

        if (!$task) {
            return false;
        }

        // Log activity before deletion
        $this->logActivity('deleted', $task, $userId, $organizationId, "Task '{$task->title}' was deleted");

        return $this->taskRepository->delete($taskId);
    }

    public function restoreTask(
        int $organizationId,
        int $projectId,
        int $userId,
        int $taskId
    ): ?Task {
        $project = $this->projectRepository->findByOrganization($organizationId, $projectId);

        if (!$project) {
            return null;
        }

        $task = $this->taskRepository->findTrashed($projectId, $taskId);

        if (!$task || !$task->trashed()) {
            return null;
        }

        $this->taskRepository->restore($taskId);

        // Log activity
        $this->logActivity('restored', $task, $userId, $organizationId, "Task '{$task->title}' was restored");

        return $task->fresh()->load(['assignee', 'creator', 'project']);
    }

    // Dependencies

    public function addDependency(
        int $organizationId,
        int $projectId,
        int $userId,
        int $taskId,
        int $dependsOnTaskId
    ): array {
        $project = $this->projectRepository->findByOrganization($organizationId, $projectId);

        if (!$project) {
            return ['success' => false, 'error' => 'Project not found', 'code' => 'NOT_FOUND'];
        }

        $task = $this->taskRepository->findByProject($projectId, $taskId);
        $dependsOnTask = $this->taskRepository->findByProject($projectId, $dependsOnTaskId);

        if (!$task || !$dependsOnTask) {
            return ['success' => false, 'error' => 'Task not found', 'code' => 'NOT_FOUND'];
        }

        // Prevent circular dependencies
        if ($taskId === $dependsOnTaskId) {
            return ['success' => false, 'error' => 'A task cannot depend on itself', 'code' => 'INVALID_DEPENDENCY'];
        }

        // Check if already depends
        $existingDependencies = $this->taskRepository->getDependencies($taskId);
        if ($existingDependencies->contains('id', $dependsOnTaskId)) {
            return ['success' => false, 'error' => 'Dependency already exists', 'code' => 'ALREADY_EXISTS'];
        }

        // Check for circular dependency
        if ($this->wouldCreateCircularDependency($taskId, $dependsOnTaskId)) {
            return ['success' => false, 'error' => 'This would create a circular dependency', 'code' => 'CIRCULAR_DEPENDENCY'];
        }

        $this->taskRepository->addDependency($taskId, $dependsOnTaskId);

        // Log activity
        $this->logActivity('dependency_added', $task, $userId, $organizationId, "Dependency added to task '{$task->title}'");

        return ['success' => true, 'task' => $task->fresh()->load('dependencies')];
    }

    public function removeDependency(
        int $organizationId,
        int $projectId,
        int $userId,
        int $taskId,
        int $dependsOnTaskId
    ): array {
        $project = $this->projectRepository->findByOrganization($organizationId, $projectId);

        if (!$project) {
            return ['success' => false, 'error' => 'Project not found', 'code' => 'NOT_FOUND'];
        }

        $task = $this->taskRepository->findByProject($projectId, $taskId);

        if (!$task) {
            return ['success' => false, 'error' => 'Task not found', 'code' => 'NOT_FOUND'];
        }

        $this->taskRepository->removeDependency($taskId, $dependsOnTaskId);

        // Log activity
        $this->logActivity('dependency_removed', $task, $userId, $organizationId, "Dependency removed from task '{$task->title}'");

        return ['success' => true, 'task' => $task->fresh()->load('dependencies')];
    }

    public function getDependencies(int $projectId, int $taskId): ?Collection
    {
        $task = $this->taskRepository->findByProject($projectId, $taskId);

        if (!$task) {
            return null;
        }

        return $this->taskRepository->getDependencies($taskId);
    }

    // Comments

    public function getComments(int $projectId, int $taskId): ?Collection
    {
        $task = $this->taskRepository->findByProject($projectId, $taskId);

        if (!$task) {
            return null;
        }

        return $this->taskRepository->getComments($taskId);
    }

    public function addComment(
        int $organizationId,
        int $projectId,
        int $userId,
        int $taskId,
        string $content,
        ?array $mentions = null
    ): array {
        $project = $this->projectRepository->findByOrganization($organizationId, $projectId);

        if (!$project) {
            return ['success' => false, 'error' => 'Project not found', 'code' => 'NOT_FOUND'];
        }

        $task = $this->taskRepository->findByProject($projectId, $taskId);

        if (!$task) {
            return ['success' => false, 'error' => 'Task not found', 'code' => 'NOT_FOUND'];
        }

        $comment = $this->taskRepository->addComment($taskId, $userId, $content, $mentions);

        // Log activity
        $this->logActivity('comment_added', $task, $userId, $organizationId, "Comment added to task '{$task->title}'");

        return ['success' => true, 'comment' => $comment->load('user')];
    }

    public function updateComment(
        int $organizationId,
        int $projectId,
        int $userId,
        int $taskId,
        int $commentId,
        string $content,
        ?array $mentions = null
    ): array {
        $project = $this->projectRepository->findByOrganization($organizationId, $projectId);

        if (!$project) {
            return ['success' => false, 'error' => 'Project not found', 'code' => 'NOT_FOUND'];
        }

        $task = $this->taskRepository->findByProject($projectId, $taskId);

        if (!$task) {
            return ['success' => false, 'error' => 'Task not found', 'code' => 'NOT_FOUND'];
        }

        $comment = $this->taskRepository->findComment($commentId);

        if (!$comment || $comment->task_id !== $taskId) {
            return ['success' => false, 'error' => 'Comment not found', 'code' => 'NOT_FOUND'];
        }

        // Only comment author can update
        if ($comment->user_id !== $userId) {
            return ['success' => false, 'error' => 'You can only edit your own comments', 'code' => 'FORBIDDEN'];
        }

        $updatedComment = $this->taskRepository->updateComment($commentId, $content, $mentions);

        return ['success' => true, 'comment' => $updatedComment->load('user')];
    }

    public function deleteComment(
        int $organizationId,
        int $projectId,
        int $userId,
        string $role,
        int $taskId,
        int $commentId
    ): array {
        $project = $this->projectRepository->findByOrganization($organizationId, $projectId);

        if (!$project) {
            return ['success' => false, 'error' => 'Project not found', 'code' => 'NOT_FOUND'];
        }

        $task = $this->taskRepository->findByProject($projectId, $taskId);

        if (!$task) {
            return ['success' => false, 'error' => 'Task not found', 'code' => 'NOT_FOUND'];
        }

        $comment = $this->taskRepository->findComment($commentId);

        if (!$comment || $comment->task_id !== $taskId) {
            return ['success' => false, 'error' => 'Comment not found', 'code' => 'NOT_FOUND'];
        }

        // Only comment author or managers/admins can delete
        if ($comment->user_id !== $userId && !in_array($role, ['admin', 'project_manager'])) {
            return ['success' => false, 'error' => 'You can only delete your own comments', 'code' => 'FORBIDDEN'];
        }

        $this->taskRepository->deleteComment($commentId);

        // Log activity
        $this->logActivity('comment_deleted', $task, $userId, $organizationId, "Comment deleted from task '{$task->title}'");

        return ['success' => true];
    }

    public function canManageTask(string $role): bool
    {
        return in_array($role, ['admin', 'project_manager']);
    }

    private function wouldCreateCircularDependency(int $taskId, int $dependsOnTaskId): bool
    {
        // Check if dependsOnTask depends on taskId (directly or indirectly)
        $visited = [];
        return $this->hasDependencyPath($dependsOnTaskId, $taskId, $visited);
    }

    private function hasDependencyPath(int $fromTaskId, int $toTaskId, array &$visited): bool
    {
        if ($fromTaskId === $toTaskId) {
            return true;
        }

        if (in_array($fromTaskId, $visited)) {
            return false;
        }

        $visited[] = $fromTaskId;

        $dependencies = $this->taskRepository->getDependencies($fromTaskId);

        foreach ($dependencies as $dependency) {
            if ($this->hasDependencyPath($dependency->id, $toTaskId, $visited)) {
                return true;
            }
        }

        return false;
    }

    private function logActivity(
        string $action,
        Task $task,
        int $userId,
        int $organizationId,
        string $description,
        ?array $oldValues = null,
        ?array $newValues = null
    ): void {
        ActivityLog::log($action, $task, $userId, $organizationId, $description, $oldValues, $newValues);
    }
}
