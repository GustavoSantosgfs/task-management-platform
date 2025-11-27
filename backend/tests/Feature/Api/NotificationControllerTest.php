<?php

namespace Tests\Feature\Api;

use App\Models\Notification;
use App\Models\Organization;
use App\Models\OrganizationUser;
use App\Models\User;
use App\Services\JwtService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationControllerTest extends TestCase
{
    use RefreshDatabase;

    private Organization $organization;
    private User $admin;
    private User $member;
    private string $adminToken;
    private string $memberToken;

    protected function setUp(): void
    {
        parent::setUp();

        $this->organization = Organization::factory()->create();

        $this->admin = $this->createUserWithRole('admin');
        $this->member = $this->createUserWithRole('member');

        $this->adminToken = $this->getToken($this->admin, 'admin');
        $this->memberToken = $this->getToken($this->member, 'member');
    }

    private function createUserWithRole(string $role): User
    {
        $user = User::factory()->create();
        OrganizationUser::factory()->create([
            'organization_id' => $this->organization->id,
            'user_id' => $user->id,
            'role' => $role,
        ]);

        return $user;
    }

    private function getToken(User $user, string $role): string
    {
        $jwtService = app(JwtService::class);

        return $jwtService->createTokenForUser([
            'id' => $user->id,
            'email' => $user->email,
            'role' => $role,
            'orgId' => $this->organization->id,
        ]);
    }

    private function createNotification(User $user, string $message, bool $read = false): Notification
    {
        return Notification::factory()->create([
            'user_id' => $user->id,
            'title' => 'Test Notification',
            'message' => $message,
            'read_at' => $read ? now() : null,
        ]);
    }

    // Index Tests
    public function test_index_returns_paginated_notifications(): void
    {
        $this->createNotification($this->admin, 'Test notification 1');
        $this->createNotification($this->admin, 'Test notification 2');

        $response = $this->withToken($this->adminToken)
            ->getJson('/api/notifications');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => [
                        'id',
                        'type',
                        'title',
                        'message',
                        'read_at',
                    ],
                ],
                'meta' => [
                    'page',
                    'per_page',
                    'total',
                    'total_pages',
                ],
            ])
            ->assertJson(['success' => true]);
    }

    public function test_index_without_token_returns_401(): void
    {
        $response = $this->getJson('/api/notifications');

        $response->assertStatus(401);
    }

    public function test_index_filters_unread_only(): void
    {
        $this->createNotification($this->admin, 'Unread notification', false);
        $this->createNotification($this->admin, 'Read notification', true);

        $response = $this->withToken($this->adminToken)
            ->getJson('/api/notifications?unread_only=true');

        $response->assertStatus(200);
        $notifications = $response->json('data');

        foreach ($notifications as $notification) {
            $this->assertNull($notification['read_at']);
        }
    }

    public function test_index_respects_per_page(): void
    {
        for ($i = 0; $i < 10; $i++) {
            $this->createNotification($this->admin, "Notification {$i}");
        }

        $response = $this->withToken($this->adminToken)
            ->getJson('/api/notifications?per_page=5');

        $response->assertStatus(200)
            ->assertJsonPath('meta.per_page', 5);
    }

    // Unread Count Tests
    public function test_unread_count_returns_count(): void
    {
        $this->createNotification($this->admin, 'Unread 1', false);
        $this->createNotification($this->admin, 'Unread 2', false);
        $this->createNotification($this->admin, 'Read', true);

        $response = $this->withToken($this->adminToken)
            ->getJson('/api/notifications/unread-count');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'unread_count' => 2,
                ],
            ]);
    }

    // Show Tests
    public function test_show_returns_notification_details(): void
    {
        $notification = $this->createNotification($this->admin, 'Test notification');

        $response = $this->withToken($this->adminToken)
            ->getJson("/api/notifications/{$notification->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'id' => $notification->id,
                    'title' => 'Test Notification',
                ],
            ]);
    }

    public function test_show_returns_404_for_nonexistent_notification(): void
    {
        $response = $this->withToken($this->adminToken)
            ->getJson('/api/notifications/99999');

        $response->assertStatus(404);
    }

    public function test_show_returns_404_for_other_users_notification(): void
    {
        $notification = $this->createNotification($this->admin, 'Admin notification');

        $response = $this->withToken($this->memberToken)
            ->getJson("/api/notifications/{$notification->id}");

        $response->assertStatus(404);
    }

    // Mark as Read Tests
    public function test_mark_as_read_marks_notification_as_read(): void
    {
        $notification = $this->createNotification($this->admin, 'Unread notification', false);

        $response = $this->withToken($this->adminToken)
            ->postJson("/api/notifications/{$notification->id}/read");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Notification marked as read',
            ]);

        $this->assertNotNull($notification->fresh()->read_at);
    }

    public function test_mark_as_read_returns_404_for_nonexistent_notification(): void
    {
        $response = $this->withToken($this->adminToken)
            ->postJson('/api/notifications/99999/read');

        $response->assertStatus(404);
    }

    // Mark as Unread Tests
    public function test_mark_as_unread_marks_notification_as_unread(): void
    {
        $notification = $this->createNotification($this->admin, 'Read notification', true);

        $response = $this->withToken($this->adminToken)
            ->postJson("/api/notifications/{$notification->id}/unread");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Notification marked as unread',
            ]);

        $this->assertNull($notification->fresh()->read_at);
    }

    // Mark All as Read Tests
    public function test_mark_all_as_read_marks_all_notifications_as_read(): void
    {
        $this->createNotification($this->admin, 'Unread 1', false);
        $this->createNotification($this->admin, 'Unread 2', false);
        $this->createNotification($this->admin, 'Unread 3', false);

        $response = $this->withToken($this->adminToken)
            ->postJson('/api/notifications/mark-all-read');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'marked_count' => 3,
                ],
            ]);

        $unreadCount = Notification::where('user_id', $this->admin->id)
            ->whereNull('read_at')
            ->count();

        $this->assertEquals(0, $unreadCount);
    }

    // Delete Tests
    public function test_destroy_deletes_notification(): void
    {
        $notification = $this->createNotification($this->admin, 'To be deleted');

        $response = $this->withToken($this->adminToken)
            ->deleteJson("/api/notifications/{$notification->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Notification deleted successfully',
            ]);

        $this->assertDatabaseMissing('notifications', ['id' => $notification->id]);
    }

    public function test_destroy_returns_404_for_nonexistent_notification(): void
    {
        $response = $this->withToken($this->adminToken)
            ->deleteJson('/api/notifications/99999');

        $response->assertStatus(404);
    }

    public function test_destroy_returns_404_for_other_users_notification(): void
    {
        $notification = $this->createNotification($this->admin, 'Admin notification');

        $response = $this->withToken($this->memberToken)
            ->deleteJson("/api/notifications/{$notification->id}");

        $response->assertStatus(404);
    }

    // Delete All Read Tests
    public function test_destroy_all_read_deletes_read_notifications(): void
    {
        $read1 = $this->createNotification($this->admin, 'Read 1', true);
        $read2 = $this->createNotification($this->admin, 'Read 2', true);
        $unread = $this->createNotification($this->admin, 'Unread', false);

        $response = $this->withToken($this->adminToken)
            ->deleteJson('/api/notifications/read');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'deleted_count' => 2,
                ],
            ]);

        // Verify read notifications are deleted
        $this->assertDatabaseMissing('notifications', ['id' => $read1->id]);
        $this->assertDatabaseMissing('notifications', ['id' => $read2->id]);

        // Verify unread notification still exists
        $this->assertDatabaseHas('notifications', ['id' => $unread->id]);
    }

    // User Isolation Tests
    public function test_user_only_sees_own_notifications(): void
    {
        // Create notifications for admin
        $this->createNotification($this->admin, 'Admin notification');

        // Create notifications for member
        $this->createNotification($this->member, 'Member notification');

        // Check admin sees only their notifications
        $adminResponse = $this->withToken($this->adminToken)
            ->getJson('/api/notifications');

        $adminNotifications = $adminResponse->json('data');
        foreach ($adminNotifications as $notification) {
            $dbNotification = Notification::find($notification['id']);
            $this->assertEquals($this->admin->id, $dbNotification->user_id);
        }

        // Check member sees only their notifications
        $memberResponse = $this->withToken($this->memberToken)
            ->getJson('/api/notifications');

        $memberNotifications = $memberResponse->json('data');
        foreach ($memberNotifications as $notification) {
            $dbNotification = Notification::find($notification['id']);
            $this->assertEquals($this->member->id, $dbNotification->user_id);
        }
    }
}
