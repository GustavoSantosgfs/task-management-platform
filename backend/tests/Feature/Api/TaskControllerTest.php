<?php

namespace Tests\Feature\Api;

use App\Models\Organization;
use App\Models\OrganizationUser;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\Task;
use App\Models\TaskComment;
use App\Models\User;
use App\Services\JwtService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskControllerTest extends TestCase
{
    use RefreshDatabase;

    private Organization $organization;
    private User $admin;
    private User $manager;
    private User $member;
    private string $adminToken;
    private string $managerToken;
    private string $memberToken;
    private Project $project;

    protected function setUp(): void
    {
        parent::setUp();

        $this->organization = Organization::factory()->create();

        $this->admin = $this->createUserWithRole('admin');
        $this->manager = $this->createUserWithRole('project_manager');
        $this->member = $this->createUserWithRole('member');

        $this->adminToken = $this->getToken($this->admin, 'admin');
        $this->managerToken = $this->getToken($this->manager, 'project_manager');
        $this->memberToken = $this->getToken($this->member, 'member');

        $this->project = $this->createProject();
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

    private function createProject(array $attributes = []): Project
    {
        $project = Project::factory()->create(array_merge([
            'organization_id' => $this->organization->id,
            'manager_id' => $this->manager->id,
            'created_by' => $this->admin->id,
        ], $attributes));

        // Add all users as project members
        ProjectMember::create(['project_id' => $project->id, 'user_id' => $this->manager->id]);
        ProjectMember::create(['project_id' => $project->id, 'user_id' => $this->member->id]);

        return $project;
    }

    private function createTask(array $attributes = []): Task
    {
        return Task::factory()->create(array_merge([
            'project_id' => $this->project->id,
            'created_by' => $this->manager->id,
        ], $attributes));
    }

    // Index Tests
    public function test_index_returns_paginated_tasks(): void
    {
        $this->createTask();
        $this->createTask();

        $response = $this->withToken($this->adminToken)
            ->getJson("/api/projects/{$this->project->id}/tasks");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => [
                        'id',
                        'title',
                        'status',
                        'priority',
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
        $response = $this->getJson("/api/projects/{$this->project->id}/tasks");

        $response->assertStatus(401);
    }

    public function test_index_filters_by_status(): void
    {
        $this->createTask(['status' => 'todo']);
        $this->createTask(['status' => 'in_progress']);

        $response = $this->withToken($this->adminToken)
            ->getJson("/api/projects/{$this->project->id}/tasks?status=todo");

        $response->assertStatus(200);
        $tasks = $response->json('data');

        foreach ($tasks as $task) {
            $this->assertEquals('todo', $task['status']);
        }
    }

    public function test_index_filters_by_priority(): void
    {
        $this->createTask(['priority' => 'high']);
        $this->createTask(['priority' => 'low']);

        $response = $this->withToken($this->adminToken)
            ->getJson("/api/projects/{$this->project->id}/tasks?priority=high");

        $response->assertStatus(200);
        $tasks = $response->json('data');

        foreach ($tasks as $task) {
            $this->assertEquals('high', $task['priority']);
        }
    }

    public function test_index_returns_404_for_nonexistent_project(): void
    {
        $response = $this->withToken($this->adminToken)
            ->getJson('/api/projects/99999/tasks');

        $response->assertStatus(404);
    }

    // Store Tests
    public function test_store_creates_task(): void
    {
        $response = $this->withToken($this->adminToken)
            ->postJson("/api/projects/{$this->project->id}/tasks", [
                'title' => 'New Test Task',
                'description' => 'Task description',
                'priority' => 'high',
                'status' => 'todo',
            ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'data' => [
                    'title' => 'New Test Task',
                    'description' => 'Task description',
                    'priority' => 'high',
                    'status' => 'todo',
                ],
            ]);

        $this->assertDatabaseHas('tasks', [
            'title' => 'New Test Task',
            'project_id' => $this->project->id,
        ]);
    }

    public function test_store_without_title_returns_validation_error(): void
    {
        $response = $this->withToken($this->adminToken)
            ->postJson("/api/projects/{$this->project->id}/tasks", [
                'description' => 'Missing title',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['title']);
    }

    public function test_store_with_invalid_priority_returns_validation_error(): void
    {
        $response = $this->withToken($this->adminToken)
            ->postJson("/api/projects/{$this->project->id}/tasks", [
                'title' => 'Test Task',
                'priority' => 'invalid_priority',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['priority']);
    }

    public function test_store_with_invalid_status_returns_validation_error(): void
    {
        $response = $this->withToken($this->adminToken)
            ->postJson("/api/projects/{$this->project->id}/tasks", [
                'title' => 'Test Task',
                'status' => 'invalid_status',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['status']);
    }

    public function test_store_with_assignee(): void
    {
        $response = $this->withToken($this->adminToken)
            ->postJson("/api/projects/{$this->project->id}/tasks", [
                'title' => 'Assigned Task',
                'assignee_id' => $this->member->id,
            ]);

        $response->assertStatus(201);

        // Verify the task was created with the assignee
        $this->assertDatabaseHas('tasks', [
            'title' => 'Assigned Task',
            'assignee_id' => $this->member->id,
        ]);
    }

    public function test_store_with_due_date(): void
    {
        $dueDate = now()->addDays(7)->format('Y-m-d');

        $response = $this->withToken($this->adminToken)
            ->postJson("/api/projects/{$this->project->id}/tasks", [
                'title' => 'Task with Due Date',
                'due_date' => $dueDate,
            ]);

        $response->assertStatus(201);
    }

    // Show Tests
    public function test_show_returns_task_details(): void
    {
        $task = $this->createTask();

        $response = $this->withToken($this->adminToken)
            ->getJson("/api/projects/{$this->project->id}/tasks/{$task->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'id' => $task->id,
                    'title' => $task->title,
                ],
            ]);
    }

    public function test_show_returns_404_for_nonexistent_task(): void
    {
        $response = $this->withToken($this->adminToken)
            ->getJson("/api/projects/{$this->project->id}/tasks/99999");

        $response->assertStatus(404);
    }

    // Update Tests
    public function test_update_modifies_task(): void
    {
        $task = $this->createTask();

        $response = $this->withToken($this->adminToken)
            ->putJson("/api/projects/{$this->project->id}/tasks/{$task->id}", [
                'title' => 'Updated Task Title',
                'status' => 'in_progress',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'title' => 'Updated Task Title',
                    'status' => 'in_progress',
                ],
            ]);
    }

    public function test_update_task_priority(): void
    {
        $task = $this->createTask(['priority' => 'low']);

        $response = $this->withToken($this->adminToken)
            ->putJson("/api/projects/{$this->project->id}/tasks/{$task->id}", [
                'priority' => 'critical',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.priority', 'critical');
    }

    public function test_update_returns_404_for_nonexistent_task(): void
    {
        $response = $this->withToken($this->adminToken)
            ->putJson("/api/projects/{$this->project->id}/tasks/99999", [
                'title' => 'Updated Title',
            ]);

        $response->assertStatus(404);
    }

    // Delete Tests
    public function test_destroy_deletes_task(): void
    {
        $task = $this->createTask();

        $response = $this->withToken($this->adminToken)
            ->deleteJson("/api/projects/{$this->project->id}/tasks/{$task->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Task deleted successfully',
            ]);

        $this->assertSoftDeleted('tasks', ['id' => $task->id]);
    }

    public function test_destroy_returns_404_for_nonexistent_task(): void
    {
        $response = $this->withToken($this->adminToken)
            ->deleteJson("/api/projects/{$this->project->id}/tasks/99999");

        $response->assertStatus(404);
    }

    // Restore Tests
    public function test_restore_recovers_deleted_task(): void
    {
        $task = $this->createTask();
        $task->delete();

        $response = $this->withToken($this->adminToken)
            ->postJson("/api/projects/{$this->project->id}/tasks/{$task->id}/restore");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Task restored successfully',
            ]);

        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'deleted_at' => null,
        ]);
    }

    // My Tasks Tests
    public function test_my_tasks_returns_user_assigned_tasks(): void
    {
        // Create tasks assigned to member
        $this->createTask(['assignee_id' => $this->member->id]);
        $this->createTask(['assignee_id' => $this->member->id]);

        $response = $this->withToken($this->memberToken)
            ->getJson('/api/my-tasks');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data',
                'meta',
            ]);
    }

    public function test_my_tasks_without_token_returns_401(): void
    {
        $response = $this->getJson('/api/my-tasks');

        $response->assertStatus(401);
    }

    // Dependencies Tests
    public function test_dependencies_returns_task_dependencies(): void
    {
        $task = $this->createTask();

        $response = $this->withToken($this->adminToken)
            ->getJson("/api/projects/{$this->project->id}/tasks/{$task->id}/dependencies");

        $response->assertStatus(200)
            ->assertJson(['success' => true]);
    }

    public function test_add_dependency_creates_dependency(): void
    {
        $task1 = $this->createTask();
        $task2 = $this->createTask();

        $response = $this->withToken($this->adminToken)
            ->postJson("/api/projects/{$this->project->id}/tasks/{$task1->id}/dependencies", [
                'depends_on_task_id' => $task2->id,
            ]);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);
    }

    public function test_add_dependency_prevents_self_reference(): void
    {
        $task = $this->createTask();

        $response = $this->withToken($this->adminToken)
            ->postJson("/api/projects/{$this->project->id}/tasks/{$task->id}/dependencies", [
                'depends_on_task_id' => $task->id,
            ]);

        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
                'error' => [
                    'code' => 'INVALID_DEPENDENCY',
                ],
            ]);
    }

    public function test_remove_dependency_removes_dependency(): void
    {
        $task1 = $this->createTask();
        $task2 = $this->createTask();

        // Add dependency first
        $task1->dependencies()->attach($task2->id);

        $response = $this->withToken($this->adminToken)
            ->deleteJson("/api/projects/{$this->project->id}/tasks/{$task1->id}/dependencies/{$task2->id}");

        $response->assertStatus(200)
            ->assertJson(['success' => true]);
    }

    // Comments Tests
    public function test_comments_returns_task_comments(): void
    {
        $task = $this->createTask();

        $response = $this->withToken($this->adminToken)
            ->getJson("/api/projects/{$this->project->id}/tasks/{$task->id}/comments");

        $response->assertStatus(200)
            ->assertJson(['success' => true]);
    }

    public function test_add_comment_creates_comment(): void
    {
        $task = $this->createTask();

        $response = $this->withToken($this->adminToken)
            ->postJson("/api/projects/{$this->project->id}/tasks/{$task->id}/comments", [
                'content' => 'This is a test comment',
            ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'data' => [
                    'content' => 'This is a test comment',
                ],
            ]);

        $this->assertDatabaseHas('task_comments', [
            'task_id' => $task->id,
            'content' => 'This is a test comment',
        ]);
    }

    public function test_add_comment_without_content_returns_validation_error(): void
    {
        $task = $this->createTask();

        $response = $this->withToken($this->adminToken)
            ->postJson("/api/projects/{$this->project->id}/tasks/{$task->id}/comments", []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['content']);
    }

    public function test_update_comment_modifies_comment(): void
    {
        $task = $this->createTask();
        $comment = TaskComment::factory()->create([
            'task_id' => $task->id,
            'user_id' => $this->admin->id,
            'content' => 'Original comment',
        ]);

        $response = $this->withToken($this->adminToken)
            ->putJson("/api/projects/{$this->project->id}/tasks/{$task->id}/comments/{$comment->id}", [
                'content' => 'Updated comment',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'content' => 'Updated comment',
                ],
            ]);
    }

    public function test_delete_comment_removes_comment(): void
    {
        $task = $this->createTask();
        $comment = TaskComment::factory()->create([
            'task_id' => $task->id,
            'user_id' => $this->admin->id,
            'content' => 'Comment to delete',
        ]);

        $response = $this->withToken($this->adminToken)
            ->deleteJson("/api/projects/{$this->project->id}/tasks/{$task->id}/comments/{$comment->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Comment deleted successfully',
            ]);
    }
}
