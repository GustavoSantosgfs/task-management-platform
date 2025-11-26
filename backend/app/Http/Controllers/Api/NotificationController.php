<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\NotificationResource;
use App\Services\NotificationService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    use ApiResponse;

    public function __construct(
        private NotificationService $notificationService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $filters = $request->only([
            'unread_only',
            'read_only',
            'type',
            'sort_by',
            'sort_direction',
        ]);

        $perPage = min($request->get('per_page', 20), 100);

        $notifications = $this->notificationService->getNotifications(
            $request->attributes->get('auth_user_id'),
            $filters,
            $perPage
        );

        return $this->paginatedResponse(
            $notifications->through(fn ($notification) => new NotificationResource($notification)),
            'Notifications retrieved successfully'
        );
    }

    public function unreadCount(Request $request): JsonResponse
    {
        $count = $this->notificationService->getUnreadCount(
            $request->attributes->get('auth_user_id')
        );

        return $this->successResponse(
            ['unread_count' => $count],
            'Unread count retrieved successfully'
        );
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $notification = $this->notificationService->getNotification(
            $request->attributes->get('auth_user_id'),
            $id
        );

        if (!$notification) {
            return $this->notFoundResponse('Notification not found');
        }

        return $this->successResponse(
            new NotificationResource($notification),
            'Notification retrieved successfully'
        );
    }

    public function markAsRead(Request $request, int $id): JsonResponse
    {
        $result = $this->notificationService->markAsRead(
            $request->attributes->get('auth_user_id'),
            $id
        );

        if (!$result['success']) {
            return $this->notFoundResponse($result['error']);
        }

        return $this->successResponse(
            new NotificationResource($result['notification']),
            'Notification marked as read'
        );
    }

    public function markAsUnread(Request $request, int $id): JsonResponse
    {
        $result = $this->notificationService->markAsUnread(
            $request->attributes->get('auth_user_id'),
            $id
        );

        if (!$result['success']) {
            return $this->notFoundResponse($result['error']);
        }

        return $this->successResponse(
            new NotificationResource($result['notification']),
            'Notification marked as unread'
        );
    }

    public function markAllAsRead(Request $request): JsonResponse
    {
        $count = $this->notificationService->markAllAsRead(
            $request->attributes->get('auth_user_id')
        );

        return $this->successResponse(
            ['marked_count' => $count],
            'All notifications marked as read'
        );
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $deleted = $this->notificationService->deleteNotification(
            $request->attributes->get('auth_user_id'),
            $id
        );

        if (!$deleted) {
            return $this->notFoundResponse('Notification not found');
        }

        return $this->successResponse(null, 'Notification deleted successfully');
    }

    public function destroyAllRead(Request $request): JsonResponse
    {
        $count = $this->notificationService->deleteAllRead(
            $request->attributes->get('auth_user_id')
        );

        return $this->successResponse(
            ['deleted_count' => $count],
            'Read notifications deleted successfully'
        );
    }
}
