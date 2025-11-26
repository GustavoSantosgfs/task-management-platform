<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\NotificationResource;
use App\Services\NotificationService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class NotificationController extends Controller
{
    use ApiResponse;

    public function __construct(
        private NotificationService $notificationService
    ) {}

    #[OA\Get(
        path: '/notifications',
        summary: 'List notifications',
        description: 'Get paginated notifications for the current user',
        tags: ['Notifications'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'unread_only', in: 'query', description: 'Only unread notifications', schema: new OA\Schema(type: 'boolean')),
            new OA\Parameter(name: 'read_only', in: 'query', description: 'Only read notifications', schema: new OA\Schema(type: 'boolean')),
            new OA\Parameter(name: 'type', in: 'query', description: 'Filter by notification type', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'per_page', in: 'query', description: 'Items per page', schema: new OA\Schema(type: 'integer', default: 20))
        ],
        responses: [
            new OA\Response(response: 200, description: 'Notifications retrieved successfully'),
            new OA\Response(response: 401, description: 'Unauthorized')
        ]
    )]
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

    #[OA\Get(
        path: '/notifications/unread-count',
        summary: 'Get unread count',
        description: 'Get the count of unread notifications',
        tags: ['Notifications'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(response: 200, description: 'Unread count retrieved'),
            new OA\Response(response: 401, description: 'Unauthorized')
        ]
    )]
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

    #[OA\Get(
        path: '/notifications/{id}',
        summary: 'Get notification',
        description: 'Get a specific notification',
        tags: ['Notifications'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(response: 200, description: 'Notification retrieved'),
            new OA\Response(response: 401, description: 'Unauthorized'),
            new OA\Response(response: 404, description: 'Notification not found')
        ]
    )]
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

    #[OA\Post(
        path: '/notifications/{id}/read',
        summary: 'Mark as read',
        description: 'Mark a notification as read',
        tags: ['Notifications'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(response: 200, description: 'Notification marked as read'),
            new OA\Response(response: 401, description: 'Unauthorized'),
            new OA\Response(response: 404, description: 'Notification not found')
        ]
    )]
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

    #[OA\Post(
        path: '/notifications/{id}/unread',
        summary: 'Mark as unread',
        description: 'Mark a notification as unread',
        tags: ['Notifications'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(response: 200, description: 'Notification marked as unread'),
            new OA\Response(response: 401, description: 'Unauthorized'),
            new OA\Response(response: 404, description: 'Notification not found')
        ]
    )]
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

    #[OA\Post(
        path: '/notifications/mark-all-read',
        summary: 'Mark all as read',
        description: 'Mark all notifications as read',
        tags: ['Notifications'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(response: 200, description: 'All notifications marked as read'),
            new OA\Response(response: 401, description: 'Unauthorized')
        ]
    )]
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

    #[OA\Delete(
        path: '/notifications/{id}',
        summary: 'Delete notification',
        description: 'Delete a notification',
        tags: ['Notifications'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(response: 200, description: 'Notification deleted'),
            new OA\Response(response: 401, description: 'Unauthorized'),
            new OA\Response(response: 404, description: 'Notification not found')
        ]
    )]
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

    #[OA\Delete(
        path: '/notifications/read',
        summary: 'Delete all read',
        description: 'Delete all read notifications',
        tags: ['Notifications'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(response: 200, description: 'Read notifications deleted'),
            new OA\Response(response: 401, description: 'Unauthorized')
        ]
    )]
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
