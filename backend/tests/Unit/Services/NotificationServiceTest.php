<?php

namespace Tests\Unit\Services;

use App\Models\Notification;
use App\Repositories\Interfaces\NotificationRepositoryInterface;
use App\Services\NotificationService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

class NotificationServiceTest extends TestCase
{
    private NotificationService $notificationService;
    private MockInterface $notificationRepository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->notificationRepository = Mockery::mock(NotificationRepositoryInterface::class);
        $this->notificationService = new NotificationService($this->notificationRepository);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    // getNotifications Tests

    public function test_get_notifications_calls_repository(): void
    {
        $paginator = new LengthAwarePaginator([], 0, 20);

        $this->notificationRepository->shouldReceive('getByUser')
            ->with(1, ['unread_only' => true], 10)
            ->once()
            ->andReturn($paginator);

        $result = $this->notificationService->getNotifications(1, ['unread_only' => true], 10);

        $this->assertInstanceOf(LengthAwarePaginator::class, $result);
    }

    // getUnreadNotifications Tests

    public function test_get_unread_notifications_returns_collection(): void
    {
        $notifications = new Collection([
            new Notification(['title' => 'Notification 1']),
            new Notification(['title' => 'Notification 2']),
        ]);

        $this->notificationRepository->shouldReceive('getUnreadByUser')
            ->with(1)
            ->once()
            ->andReturn($notifications);

        $result = $this->notificationService->getUnreadNotifications(1);

        $this->assertInstanceOf(Collection::class, $result);
        $this->assertCount(2, $result);
    }

    // getUnreadCount Tests

    public function test_get_unread_count_returns_count(): void
    {
        $this->notificationRepository->shouldReceive('getUnreadCount')
            ->with(1)
            ->once()
            ->andReturn(5);

        $result = $this->notificationService->getUnreadCount(1);

        $this->assertEquals(5, $result);
    }

    // getNotification Tests

    public function test_get_notification_returns_notification(): void
    {
        $notification = new Notification(['title' => 'Test']);
        $notification->id = 1;

        $this->notificationRepository->shouldReceive('findByUser')
            ->with(1, 1)
            ->once()
            ->andReturn($notification);

        $result = $this->notificationService->getNotification(1, 1);

        $this->assertInstanceOf(Notification::class, $result);
    }

    public function test_get_notification_returns_null_when_not_found(): void
    {
        $this->notificationRepository->shouldReceive('findByUser')
            ->with(1, 999)
            ->once()
            ->andReturn(null);

        $result = $this->notificationService->getNotification(1, 999);

        $this->assertNull($result);
    }

    // markAsRead Tests

    public function test_mark_as_read_returns_success(): void
    {
        $notification = new Notification(['title' => 'Test']);
        $notification->id = 1;

        $this->notificationRepository->shouldReceive('findByUser')
            ->with(1, 1)
            ->once()
            ->andReturn($notification);

        $this->notificationRepository->shouldReceive('markAsRead')
            ->with(1)
            ->once()
            ->andReturn(true);

        $result = $this->notificationService->markAsRead(1, 1);

        $this->assertTrue($result['success']);
    }

    public function test_mark_as_read_returns_not_found_when_notification_missing(): void
    {
        $this->notificationRepository->shouldReceive('findByUser')
            ->with(1, 999)
            ->once()
            ->andReturn(null);

        $result = $this->notificationService->markAsRead(1, 999);

        $this->assertFalse($result['success']);
        $this->assertEquals('NOT_FOUND', $result['code']);
    }

    // markAsUnread Tests

    public function test_mark_as_unread_returns_success(): void
    {
        $notification = new Notification(['title' => 'Test']);
        $notification->id = 1;

        $this->notificationRepository->shouldReceive('findByUser')
            ->with(1, 1)
            ->once()
            ->andReturn($notification);

        $this->notificationRepository->shouldReceive('markAsUnread')
            ->with(1)
            ->once()
            ->andReturn(true);

        $result = $this->notificationService->markAsUnread(1, 1);

        $this->assertTrue($result['success']);
    }

    public function test_mark_as_unread_returns_not_found_when_notification_missing(): void
    {
        $this->notificationRepository->shouldReceive('findByUser')
            ->with(1, 999)
            ->once()
            ->andReturn(null);

        $result = $this->notificationService->markAsUnread(1, 999);

        $this->assertFalse($result['success']);
        $this->assertEquals('NOT_FOUND', $result['code']);
    }

    // markAllAsRead Tests

    public function test_mark_all_as_read_returns_count(): void
    {
        $this->notificationRepository->shouldReceive('markAllAsRead')
            ->with(1)
            ->once()
            ->andReturn(5);

        $result = $this->notificationService->markAllAsRead(1);

        $this->assertEquals(5, $result);
    }

    // deleteNotification Tests

    public function test_delete_notification_returns_true_on_success(): void
    {
        $this->notificationRepository->shouldReceive('deleteByUser')
            ->with(1, 1)
            ->once()
            ->andReturn(true);

        $result = $this->notificationService->deleteNotification(1, 1);

        $this->assertTrue($result);
    }

    public function test_delete_notification_returns_false_on_failure(): void
    {
        $this->notificationRepository->shouldReceive('deleteByUser')
            ->with(1, 999)
            ->once()
            ->andReturn(false);

        $result = $this->notificationService->deleteNotification(1, 999);

        $this->assertFalse($result);
    }

    // deleteAllRead Tests

    public function test_delete_all_read_returns_count(): void
    {
        $this->notificationRepository->shouldReceive('deleteAllRead')
            ->with(1)
            ->once()
            ->andReturn(3);

        $result = $this->notificationService->deleteAllRead(1);

        $this->assertEquals(3, $result);
    }

    // createNotification Tests

    public function test_create_notification_returns_notification(): void
    {
        $notification = new Notification([
            'user_id' => 1,
            'type' => 'test',
            'title' => 'Test Title',
            'message' => 'Test message',
        ]);

        $this->notificationRepository->shouldReceive('createNotification')
            ->with(1, 'test', 'Test Title', 'Test message', ['key' => 'value'])
            ->once()
            ->andReturn($notification);

        $result = $this->notificationService->createNotification(
            1,
            'test',
            'Test Title',
            'Test message',
            ['key' => 'value']
        );

        $this->assertInstanceOf(Notification::class, $result);
    }

    // Helper method tests

    public function test_notify_task_assigned_creates_correct_notification(): void
    {
        $notification = new Notification([
            'type' => 'task_assigned',
            'title' => 'Task Assigned',
        ]);

        $this->notificationRepository->shouldReceive('createNotification')
            ->with(
                1,
                'task_assigned',
                'Task Assigned',
                'You have been assigned to task: Test Task',
                ['task_id' => 10, 'assigned_by' => 5]
            )
            ->once()
            ->andReturn($notification);

        $result = $this->notificationService->notifyTaskAssigned(1, 10, 'Test Task', 5);

        $this->assertInstanceOf(Notification::class, $result);
        $this->assertEquals('task_assigned', $result->type);
    }

    public function test_notify_task_comment_creates_correct_notification(): void
    {
        $notification = new Notification([
            'type' => 'task_comment',
            'title' => 'New Comment',
        ]);

        $this->notificationRepository->shouldReceive('createNotification')
            ->with(
                1,
                'task_comment',
                'New Comment',
                'New comment on task: Test Task',
                ['task_id' => 10, 'comment_by' => 5]
            )
            ->once()
            ->andReturn($notification);

        $result = $this->notificationService->notifyTaskComment(1, 10, 'Test Task', 5);

        $this->assertInstanceOf(Notification::class, $result);
        $this->assertEquals('task_comment', $result->type);
    }

    public function test_notify_task_status_changed_creates_correct_notification(): void
    {
        $notification = new Notification([
            'type' => 'task_status_changed',
            'title' => 'Task Status Updated',
        ]);

        $this->notificationRepository->shouldReceive('createNotification')
            ->with(
                1,
                'task_status_changed',
                'Task Status Updated',
                "Task 'Test Task' status changed to: done",
                ['task_id' => 10, 'new_status' => 'done']
            )
            ->once()
            ->andReturn($notification);

        $result = $this->notificationService->notifyTaskStatusChanged(1, 10, 'Test Task', 'done');

        $this->assertInstanceOf(Notification::class, $result);
        $this->assertEquals('task_status_changed', $result->type);
    }

    public function test_notify_mention_creates_correct_notification(): void
    {
        $notification = new Notification([
            'type' => 'mention',
            'title' => 'You were mentioned',
        ]);

        $this->notificationRepository->shouldReceive('createNotification')
            ->with(
                1,
                'mention',
                'You were mentioned',
                'You were mentioned in a comment on task: Test Task',
                ['task_id' => 10, 'mentioned_by' => 5]
            )
            ->once()
            ->andReturn($notification);

        $result = $this->notificationService->notifyMention(1, 10, 'Test Task', 5);

        $this->assertInstanceOf(Notification::class, $result);
        $this->assertEquals('mention', $result->type);
    }

    public function test_notify_project_invite_creates_correct_notification(): void
    {
        $notification = new Notification([
            'type' => 'project_invite',
            'title' => 'Project Invitation',
        ]);

        $this->notificationRepository->shouldReceive('createNotification')
            ->with(
                1,
                'project_invite',
                'Project Invitation',
                'You have been added to project: Test Project',
                ['project_id' => 10, 'invited_by' => 5]
            )
            ->once()
            ->andReturn($notification);

        $result = $this->notificationService->notifyProjectInvite(1, 10, 'Test Project', 5);

        $this->assertInstanceOf(Notification::class, $result);
        $this->assertEquals('project_invite', $result->type);
    }

    public function test_notify_task_due_soon_creates_correct_notification(): void
    {
        $notification = new Notification([
            'type' => 'task_due_soon',
            'title' => 'Task Due Soon',
        ]);

        $this->notificationRepository->shouldReceive('createNotification')
            ->with(
                1,
                'task_due_soon',
                'Task Due Soon',
                "Task 'Test Task' is due on 2024-12-31",
                ['task_id' => 10, 'due_date' => '2024-12-31']
            )
            ->once()
            ->andReturn($notification);

        $result = $this->notificationService->notifyTaskDueSoon(1, 10, 'Test Task', '2024-12-31');

        $this->assertInstanceOf(Notification::class, $result);
        $this->assertEquals('task_due_soon', $result->type);
    }
}
