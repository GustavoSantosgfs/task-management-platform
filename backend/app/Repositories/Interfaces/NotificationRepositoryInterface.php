<?php

namespace App\Repositories\Interfaces;

use App\Models\Notification;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

interface NotificationRepositoryInterface extends RepositoryInterface
{
    public function getByUser(int $userId, array $filters = [], int $perPage = 20): LengthAwarePaginator;

    public function getUnreadByUser(int $userId): Collection;

    public function getUnreadCount(int $userId): int;

    public function findByUser(int $userId, int $notificationId): ?Notification;

    public function markAsRead(int $notificationId): bool;

    public function markAsUnread(int $notificationId): bool;

    public function markAllAsRead(int $userId): int;

    public function deleteByUser(int $userId, int $notificationId): bool;

    public function deleteAllRead(int $userId): int;

    public function createNotification(
        int $userId,
        string $type,
        string $title,
        string $message,
        array $data = []
    ): Notification;
}
