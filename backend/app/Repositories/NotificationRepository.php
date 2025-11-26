<?php

namespace App\Repositories;

use App\Models\Notification;
use App\Repositories\Interfaces\NotificationRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class NotificationRepository extends BaseRepository implements NotificationRepositoryInterface
{
    public function __construct(Notification $model)
    {
        parent::__construct($model);
    }

    public function getByUser(int $userId, array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        $query = $this->model->where('user_id', $userId);

        // Filter by read status
        if (isset($filters['unread_only']) && $filters['unread_only']) {
            $query->whereNull('read_at');
        }

        if (isset($filters['read_only']) && $filters['read_only']) {
            $query->whereNotNull('read_at');
        }

        // Filter by type
        if (!empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        // Sorting (most recent first by default)
        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortDirection = $filters['sort_direction'] ?? 'desc';
        $query->orderBy($sortBy, $sortDirection);

        return $query->paginate($perPage);
    }

    public function getUnreadByUser(int $userId): Collection
    {
        return $this->model
            ->where('user_id', $userId)
            ->whereNull('read_at')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function getUnreadCount(int $userId): int
    {
        return $this->model
            ->where('user_id', $userId)
            ->whereNull('read_at')
            ->count();
    }

    public function findByUser(int $userId, int $notificationId): ?Notification
    {
        return $this->model
            ->where('user_id', $userId)
            ->where('id', $notificationId)
            ->first();
    }

    public function markAsRead(int $notificationId): bool
    {
        $notification = $this->find($notificationId);
        if (!$notification) {
            return false;
        }

        return $notification->update(['read_at' => now()]);
    }

    public function markAsUnread(int $notificationId): bool
    {
        $notification = $this->find($notificationId);
        if (!$notification) {
            return false;
        }

        return $notification->update(['read_at' => null]);
    }

    public function markAllAsRead(int $userId): int
    {
        return $this->model
            ->where('user_id', $userId)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
    }

    public function deleteByUser(int $userId, int $notificationId): bool
    {
        $notification = $this->findByUser($userId, $notificationId);
        if (!$notification) {
            return false;
        }

        return $notification->delete();
    }

    public function deleteAllRead(int $userId): int
    {
        return $this->model
            ->where('user_id', $userId)
            ->whereNotNull('read_at')
            ->delete();
    }

    public function createNotification(
        int $userId,
        string $type,
        string $title,
        string $message,
        array $data = []
    ): Notification {
        return $this->model->create([
            'user_id' => $userId,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'data' => $data,
        ]);
    }
}
