<?php

namespace App\Services;

use App\Models\Notification;
use App\Repositories\Interfaces\NotificationRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class NotificationService
{
    public function __construct(
        private NotificationRepositoryInterface $notificationRepository
    ) {}

    public function getNotifications(
        int $userId,
        array $filters = [],
        int $perPage = 20
    ): LengthAwarePaginator {
        return $this->notificationRepository->getByUser($userId, $filters, $perPage);
    }

    public function getUnreadNotifications(int $userId): Collection
    {
        return $this->notificationRepository->getUnreadByUser($userId);
    }

    public function getUnreadCount(int $userId): int
    {
        return $this->notificationRepository->getUnreadCount($userId);
    }

    public function getNotification(int $userId, int $notificationId): ?Notification
    {
        return $this->notificationRepository->findByUser($userId, $notificationId);
    }

    public function markAsRead(int $userId, int $notificationId): array
    {
        $notification = $this->notificationRepository->findByUser($userId, $notificationId);

        if (!$notification) {
            return ['success' => false, 'error' => 'Notification not found', 'code' => 'NOT_FOUND'];
        }

        $this->notificationRepository->markAsRead($notificationId);

        return ['success' => true, 'notification' => $notification->fresh()];
    }

    public function markAsUnread(int $userId, int $notificationId): array
    {
        $notification = $this->notificationRepository->findByUser($userId, $notificationId);

        if (!$notification) {
            return ['success' => false, 'error' => 'Notification not found', 'code' => 'NOT_FOUND'];
        }

        $this->notificationRepository->markAsUnread($notificationId);

        return ['success' => true, 'notification' => $notification->fresh()];
    }

    public function markAllAsRead(int $userId): int
    {
        return $this->notificationRepository->markAllAsRead($userId);
    }

    public function deleteNotification(int $userId, int $notificationId): bool
    {
        return $this->notificationRepository->deleteByUser($userId, $notificationId);
    }

    public function deleteAllRead(int $userId): int
    {
        return $this->notificationRepository->deleteAllRead($userId);
    }

    public function createNotification(
        int $userId,
        string $type,
        string $title,
        string $message,
        array $data = []
    ): Notification {
        return $this->notificationRepository->createNotification(
            $userId,
            $type,
            $title,
            $message,
            $data
        );
    }

    // Helper methods for creating specific notification types

    public function notifyTaskAssigned(int $userId, int $taskId, string $taskTitle, int $assignedBy): Notification
    {
        return $this->createNotification(
            $userId,
            'task_assigned',
            'Task Assigned',
            "You have been assigned to task: {$taskTitle}",
            [
                'task_id' => $taskId,
                'assigned_by' => $assignedBy,
            ]
        );
    }

    public function notifyTaskComment(int $userId, int $taskId, string $taskTitle, int $commentBy): Notification
    {
        return $this->createNotification(
            $userId,
            'task_comment',
            'New Comment',
            "New comment on task: {$taskTitle}",
            [
                'task_id' => $taskId,
                'comment_by' => $commentBy,
            ]
        );
    }

    public function notifyTaskStatusChanged(int $userId, int $taskId, string $taskTitle, string $newStatus): Notification
    {
        return $this->createNotification(
            $userId,
            'task_status_changed',
            'Task Status Updated',
            "Task '{$taskTitle}' status changed to: {$newStatus}",
            [
                'task_id' => $taskId,
                'new_status' => $newStatus,
            ]
        );
    }

    public function notifyMention(int $userId, int $taskId, string $taskTitle, int $mentionedBy): Notification
    {
        return $this->createNotification(
            $userId,
            'mention',
            'You were mentioned',
            "You were mentioned in a comment on task: {$taskTitle}",
            [
                'task_id' => $taskId,
                'mentioned_by' => $mentionedBy,
            ]
        );
    }

    public function notifyProjectInvite(int $userId, int $projectId, string $projectTitle, int $invitedBy): Notification
    {
        return $this->createNotification(
            $userId,
            'project_invite',
            'Project Invitation',
            "You have been added to project: {$projectTitle}",
            [
                'project_id' => $projectId,
                'invited_by' => $invitedBy,
            ]
        );
    }

    public function notifyTaskDueSoon(int $userId, int $taskId, string $taskTitle, string $dueDate): Notification
    {
        return $this->createNotification(
            $userId,
            'task_due_soon',
            'Task Due Soon',
            "Task '{$taskTitle}' is due on {$dueDate}",
            [
                'task_id' => $taskId,
                'due_date' => $dueDate,
            ]
        );
    }
}
