<?php

namespace Tests\Unit\Services;

use App\Models\Organization;
use App\Models\OrganizationUser;
use App\Models\Project;
use App\Models\User;
use App\Repositories\Interfaces\ProjectRepositoryInterface;
use App\Services\ProjectService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

class ProjectServiceTest extends TestCase
{
    use RefreshDatabase;

    private ProjectService $projectService;
    private MockInterface $projectRepository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->projectRepository = Mockery::mock(ProjectRepositoryInterface::class);
        $this->projectService = new ProjectService($this->projectRepository);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    // canAccessProject Tests

    public function test_admin_can_access_any_project(): void
    {
        $project = new Project(['visibility' => 'private']);
        $project->id = 1;

        $this->projectRepository->shouldReceive('isMember')->never();

        $result = $this->projectService->canAccessProject($project, 1, 'admin');

        $this->assertTrue($result);
    }

    public function test_project_manager_can_access_any_project(): void
    {
        $project = new Project(['visibility' => 'private']);
        $project->id = 1;

        $this->projectRepository->shouldReceive('isMember')->never();

        $result = $this->projectService->canAccessProject($project, 1, 'project_manager');

        $this->assertTrue($result);
    }

    public function test_member_can_access_public_project(): void
    {
        $project = new Project(['visibility' => 'public']);
        $project->id = 1;

        $this->projectRepository->shouldReceive('isMember')->never();

        $result = $this->projectService->canAccessProject($project, 1, 'member');

        $this->assertTrue($result);
    }

    public function test_member_can_access_private_project_if_member(): void
    {
        $project = new Project(['visibility' => 'private']);
        $project->id = 1;

        $this->projectRepository->shouldReceive('isMember')
            ->with(1, 1)
            ->once()
            ->andReturn(true);

        $result = $this->projectService->canAccessProject($project, 1, 'member');

        $this->assertTrue($result);
    }

    public function test_member_cannot_access_private_project_if_not_member(): void
    {
        $project = new Project(['visibility' => 'private']);
        $project->id = 1;

        $this->projectRepository->shouldReceive('isMember')
            ->with(1, 1)
            ->once()
            ->andReturn(false);

        $result = $this->projectService->canAccessProject($project, 1, 'member');

        $this->assertFalse($result);
    }

    // canManageProject Tests

    public function test_admin_can_manage_project(): void
    {
        $result = $this->projectService->canManageProject('admin');

        $this->assertTrue($result);
    }

    public function test_project_manager_can_manage_project(): void
    {
        $result = $this->projectService->canManageProject('project_manager');

        $this->assertTrue($result);
    }

    public function test_member_cannot_manage_project(): void
    {
        $result = $this->projectService->canManageProject('member');

        $this->assertFalse($result);
    }

    // getProject Tests

    public function test_get_project_returns_null_when_not_found(): void
    {
        $this->projectRepository->shouldReceive('findByOrganization')
            ->with(1, 999)
            ->once()
            ->andReturn(null);

        $result = $this->projectService->getProject(1, 1, 'admin', 999);

        $this->assertNull($result);
    }

    public function test_get_project_returns_project_for_admin(): void
    {
        $project = new Project(['visibility' => 'private', 'title' => 'Test Project']);
        $project->id = 1;

        $this->projectRepository->shouldReceive('findByOrganization')
            ->with(1, 1)
            ->once()
            ->andReturn($project);

        $this->projectRepository->shouldReceive('findWithDetails')
            ->with(1)
            ->once()
            ->andReturn($project);

        $result = $this->projectService->getProject(1, 1, 'admin', 1);

        $this->assertInstanceOf(Project::class, $result);
    }

    public function test_get_project_returns_null_for_unauthorized_member(): void
    {
        $project = new Project(['visibility' => 'private']);
        $project->id = 1;

        $this->projectRepository->shouldReceive('findByOrganization')
            ->with(1, 1)
            ->once()
            ->andReturn($project);

        $this->projectRepository->shouldReceive('isMember')
            ->with(1, 2)
            ->once()
            ->andReturn(false);

        $result = $this->projectService->getProject(1, 2, 'member', 1);

        $this->assertNull($result);
    }

    // addMember Tests

    public function test_add_member_returns_not_found_when_project_missing(): void
    {
        $this->projectRepository->shouldReceive('findByOrganization')
            ->with(1, 999)
            ->once()
            ->andReturn(null);

        $result = $this->projectService->addMember(1, 1, 999, 2);

        $this->assertFalse($result['success']);
        $this->assertEquals('NOT_FOUND', $result['code']);
    }

    public function test_add_member_returns_error_when_user_not_in_organization(): void
    {
        $organization = Organization::factory()->create();
        $project = new Project(['organization_id' => $organization->id]);
        $project->id = 1;

        $this->projectRepository->shouldReceive('findByOrganization')
            ->with($organization->id, 1)
            ->once()
            ->andReturn($project);

        $result = $this->projectService->addMember($organization->id, 1, 1, 999);

        $this->assertFalse($result['success']);
        $this->assertEquals('INVALID_USER', $result['code']);
    }

    public function test_add_member_returns_error_when_already_member(): void
    {
        $organization = Organization::factory()->create();
        $user = User::factory()->create();
        OrganizationUser::factory()->create([
            'organization_id' => $organization->id,
            'user_id' => $user->id,
            'role' => 'member',
        ]);

        $project = new Project(['organization_id' => $organization->id]);
        $project->id = 1;

        $this->projectRepository->shouldReceive('findByOrganization')
            ->with($organization->id, 1)
            ->once()
            ->andReturn($project);

        $this->projectRepository->shouldReceive('isMember')
            ->with(1, $user->id)
            ->once()
            ->andReturn(true);

        $result = $this->projectService->addMember($organization->id, 1, 1, $user->id);

        $this->assertFalse($result['success']);
        $this->assertEquals('ALREADY_MEMBER', $result['code']);
    }

    // removeMember Tests

    public function test_remove_member_returns_not_found_when_project_missing(): void
    {
        $this->projectRepository->shouldReceive('findByOrganization')
            ->with(1, 999)
            ->once()
            ->andReturn(null);

        $result = $this->projectService->removeMember(1, 1, 999, 2);

        $this->assertFalse($result['success']);
        $this->assertEquals('NOT_FOUND', $result['code']);
    }

    public function test_remove_member_cannot_remove_project_manager(): void
    {
        $project = new Project(['manager_id' => 5]);
        $project->id = 1;

        $this->projectRepository->shouldReceive('findByOrganization')
            ->with(1, 1)
            ->once()
            ->andReturn($project);

        $result = $this->projectService->removeMember(1, 1, 1, 5);

        $this->assertFalse($result['success']);
        $this->assertEquals('CANNOT_REMOVE_MANAGER', $result['code']);
    }

    public function test_remove_member_returns_not_found_when_not_member(): void
    {
        $project = new Project(['manager_id' => 1]);
        $project->id = 1;

        $this->projectRepository->shouldReceive('findByOrganization')
            ->with(1, 1)
            ->once()
            ->andReturn($project);

        $this->projectRepository->shouldReceive('isMember')
            ->with(1, 2)
            ->once()
            ->andReturn(false);

        $result = $this->projectService->removeMember(1, 1, 1, 2);

        $this->assertFalse($result['success']);
        $this->assertEquals('NOT_FOUND', $result['code']);
    }

    // getMembers Tests

    public function test_get_members_returns_null_when_project_not_found(): void
    {
        $this->projectRepository->shouldReceive('findByOrganization')
            ->with(1, 999)
            ->once()
            ->andReturn(null);

        $result = $this->projectService->getMembers(1, 999);

        $this->assertNull($result);
    }

    public function test_get_members_returns_collection_for_valid_project(): void
    {
        $project = new Project();
        $project->id = 1;

        // Use Eloquent Collection instead of Support Collection
        $members = new \Illuminate\Database\Eloquent\Collection([
            new User(['name' => 'User 1']),
            new User(['name' => 'User 2']),
        ]);

        $this->projectRepository->shouldReceive('findByOrganization')
            ->with(1, 1)
            ->once()
            ->andReturn($project);

        $this->projectRepository->shouldReceive('getMembers')
            ->with(1)
            ->once()
            ->andReturn($members);

        $result = $this->projectService->getMembers(1, 1);

        $this->assertInstanceOf(Collection::class, $result);
        $this->assertCount(2, $result);
    }

    // deleteProject Tests

    public function test_delete_project_returns_false_when_not_found(): void
    {
        $this->projectRepository->shouldReceive('findByOrganization')
            ->with(1, 999)
            ->once()
            ->andReturn(null);

        $result = $this->projectService->deleteProject(1, 1, 999);

        $this->assertFalse($result);
    }

    // getProjects Tests

    public function test_get_projects_calls_repository_with_correct_params(): void
    {
        $paginator = new LengthAwarePaginator([], 0, 20);

        $this->projectRepository->shouldReceive('getAccessibleProjects')
            ->with(1, 1, 'admin', ['status' => 'active'], 10)
            ->once()
            ->andReturn($paginator);

        $result = $this->projectService->getProjects(1, 1, 'admin', ['status' => 'active'], 10);

        $this->assertInstanceOf(LengthAwarePaginator::class, $result);
    }
}
