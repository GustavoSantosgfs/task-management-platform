<?php

namespace Tests\Feature\Api;

use App\Models\Organization;
use App\Models\OrganizationUser;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\User;
use App\Services\JwtService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectControllerTest extends TestCase
{
    use RefreshDatabase;

    private Organization $organization;
    private User $admin;
    private User $manager;
    private User $member;
    private string $adminToken;
    private string $managerToken;
    private string $memberToken;

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

        // Add manager as project member
        ProjectMember::create([
            'project_id' => $project->id,
            'user_id' => $this->manager->id,
        ]);

        return $project;
    }

    // Index Tests
    public function test_index_returns_paginated_projects(): void
    {
        $this->createProject();
        $this->createProject();

        $response = $this->withToken($this->adminToken)
            ->getJson('/api/projects');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => [
                        'id',
                        'title',
                        'description',
                        'status',
                        'visibility',
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
        $response = $this->getJson('/api/projects');

        $response->assertStatus(401);
    }

    public function test_index_filters_by_status(): void
    {
        $this->createProject(['status' => 'active']);
        $this->createProject(['status' => 'planning']);

        $response = $this->withToken($this->adminToken)
            ->getJson('/api/projects?status=active');

        $response->assertStatus(200);
        $projects = $response->json('data');

        foreach ($projects as $project) {
            $this->assertEquals('active', $project['status']);
        }
    }

    public function test_index_filters_by_visibility(): void
    {
        $this->createProject(['visibility' => 'public']);
        $this->createProject(['visibility' => 'private']);

        $response = $this->withToken($this->adminToken)
            ->getJson('/api/projects?visibility=public');

        $response->assertStatus(200);
        $projects = $response->json('data');

        foreach ($projects as $project) {
            $this->assertEquals('public', $project['visibility']);
        }
    }

    public function test_index_respects_per_page_parameter(): void
    {
        for ($i = 0; $i < 10; $i++) {
            $this->createProject();
        }

        $response = $this->withToken($this->adminToken)
            ->getJson('/api/projects?per_page=5');

        $response->assertStatus(200)
            ->assertJsonPath('meta.per_page', 5);
    }

    // Store Tests
    public function test_store_creates_project_as_admin(): void
    {
        $response = $this->withToken($this->adminToken)
            ->postJson('/api/projects', [
                'title' => 'New Test Project',
                'description' => 'A test project description',
                'status' => 'planning',
                'visibility' => 'public',
            ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'data' => [
                    'title' => 'New Test Project',
                    'description' => 'A test project description',
                    'status' => 'planning',
                    'visibility' => 'public',
                ],
            ]);

        $this->assertDatabaseHas('projects', [
            'title' => 'New Test Project',
        ]);
    }

    public function test_store_creates_project_as_manager(): void
    {
        $response = $this->withToken($this->managerToken)
            ->postJson('/api/projects', [
                'title' => 'Manager Project',
                'status' => 'planning',
            ]);

        $response->assertStatus(201);
    }

    public function test_store_without_title_returns_validation_error(): void
    {
        $response = $this->withToken($this->adminToken)
            ->postJson('/api/projects', [
                'description' => 'Missing title',
            ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'error' => [
                    'code' => 'VALIDATION_ERROR',
                ],
            ])
            ->assertJsonStructure([
                'error' => [
                    'details' => ['title'],
                ],
            ]);
    }

    public function test_store_with_invalid_status_returns_validation_error(): void
    {
        $response = $this->withToken($this->adminToken)
            ->postJson('/api/projects', [
                'title' => 'Test Project',
                'status' => 'invalid_status',
            ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'error' => [
                    'code' => 'VALIDATION_ERROR',
                ],
            ])
            ->assertJsonStructure([
                'error' => [
                    'details' => ['status'],
                ],
            ]);
    }

    public function test_store_adds_creator_as_member(): void
    {
        $response = $this->withToken($this->adminToken)
            ->postJson('/api/projects', [
                'title' => 'Project With Member',
            ]);

        $response->assertStatus(201);
        $projectId = $response->json('data.id');

        $this->assertDatabaseHas('project_members', [
            'project_id' => $projectId,
            'user_id' => $this->admin->id,
        ]);
    }

    // Show Tests
    public function test_show_returns_project_details(): void
    {
        $project = $this->createProject();

        $response = $this->withToken($this->adminToken)
            ->getJson("/api/projects/{$project->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'id' => $project->id,
                    'title' => $project->title,
                ],
            ]);
    }

    public function test_show_returns_404_for_nonexistent_project(): void
    {
        $response = $this->withToken($this->adminToken)
            ->getJson('/api/projects/99999');

        $response->assertStatus(404);
    }

    public function test_show_public_project_accessible_by_member(): void
    {
        $project = $this->createProject(['visibility' => 'public']);

        $response = $this->withToken($this->memberToken)
            ->getJson("/api/projects/{$project->id}");

        $response->assertStatus(200);
    }

    // Update Tests
    public function test_update_modifies_project(): void
    {
        $project = $this->createProject();

        $response = $this->withToken($this->adminToken)
            ->putJson("/api/projects/{$project->id}", [
                'title' => 'Updated Title',
                'description' => 'Updated description',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'title' => 'Updated Title',
                    'description' => 'Updated description',
                ],
            ]);
    }

    public function test_update_returns_404_for_nonexistent_project(): void
    {
        $response = $this->withToken($this->adminToken)
            ->putJson('/api/projects/99999', [
                'title' => 'Updated Title',
            ]);

        $response->assertStatus(404);
    }

    public function test_update_status_to_completed(): void
    {
        $project = $this->createProject();

        $response = $this->withToken($this->adminToken)
            ->putJson("/api/projects/{$project->id}", [
                'status' => 'completed',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'status' => 'completed',
                ],
            ]);
    }

    // Delete (Archive) Tests
    public function test_destroy_archives_project_as_admin(): void
    {
        $project = $this->createProject();

        $response = $this->withToken($this->adminToken)
            ->deleteJson("/api/projects/{$project->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Project archived successfully',
            ]);

        $this->assertSoftDeleted('projects', ['id' => $project->id]);
    }

    public function test_destroy_archives_project_as_manager(): void
    {
        $project = $this->createProject();

        $response = $this->withToken($this->managerToken)
            ->deleteJson("/api/projects/{$project->id}");

        $response->assertStatus(200);
    }

    public function test_destroy_forbidden_for_member(): void
    {
        $project = $this->createProject();

        $response = $this->withToken($this->memberToken)
            ->deleteJson("/api/projects/{$project->id}");

        $response->assertStatus(403);
    }

    public function test_destroy_returns_404_for_nonexistent_project(): void
    {
        $response = $this->withToken($this->adminToken)
            ->deleteJson('/api/projects/99999');

        $response->assertStatus(404);
    }

    // Restore Tests
    public function test_restore_recovers_archived_project(): void
    {
        $project = $this->createProject();
        $project->delete();

        $response = $this->withToken($this->adminToken)
            ->postJson("/api/projects/{$project->id}/restore");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Project restored successfully',
            ]);

        $this->assertDatabaseHas('projects', [
            'id' => $project->id,
            'deleted_at' => null,
        ]);
    }

    public function test_restore_forbidden_for_member(): void
    {
        $project = $this->createProject();
        $project->delete();

        $response = $this->withToken($this->memberToken)
            ->postJson("/api/projects/{$project->id}/restore");

        $response->assertStatus(403);
    }

    // Members Tests
    public function test_members_returns_project_members(): void
    {
        $project = $this->createProject();

        $response = $this->withToken($this->adminToken)
            ->getJson("/api/projects/{$project->id}/members");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'email',
                    ],
                ],
            ]);
    }

    public function test_add_member_adds_user_to_project(): void
    {
        $project = $this->createProject();
        $newMember = $this->createUserWithRole('member');

        $response = $this->withToken($this->adminToken)
            ->postJson("/api/projects/{$project->id}/members", [
                'user_id' => $newMember->id,
            ]);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('project_members', [
            'project_id' => $project->id,
            'user_id' => $newMember->id,
        ]);
    }

    public function test_add_member_returns_error_for_duplicate(): void
    {
        $project = $this->createProject();

        // Manager is already a member
        $response = $this->withToken($this->adminToken)
            ->postJson("/api/projects/{$project->id}/members", [
                'user_id' => $this->manager->id,
            ]);

        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
                'error' => [
                    'code' => 'ALREADY_MEMBER',
                ],
            ]);
    }

    public function test_add_member_forbidden_for_member_role(): void
    {
        $project = $this->createProject();
        $newMember = $this->createUserWithRole('member');

        $response = $this->withToken($this->memberToken)
            ->postJson("/api/projects/{$project->id}/members", [
                'user_id' => $newMember->id,
            ]);

        $response->assertStatus(403);
    }

    public function test_remove_member_removes_user_from_project(): void
    {
        $project = $this->createProject();
        $memberToRemove = $this->createUserWithRole('member');

        // Add member first
        ProjectMember::create([
            'project_id' => $project->id,
            'user_id' => $memberToRemove->id,
        ]);

        $response = $this->withToken($this->adminToken)
            ->deleteJson("/api/projects/{$project->id}/members/{$memberToRemove->id}");

        $response->assertStatus(200);

        $this->assertDatabaseMissing('project_members', [
            'project_id' => $project->id,
            'user_id' => $memberToRemove->id,
        ]);
    }

    public function test_remove_member_cannot_remove_manager(): void
    {
        $project = $this->createProject();

        $response = $this->withToken($this->adminToken)
            ->deleteJson("/api/projects/{$project->id}/members/{$project->manager_id}");

        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
                'error' => [
                    'code' => 'CANNOT_REMOVE_MANAGER',
                ],
            ]);
    }
}
