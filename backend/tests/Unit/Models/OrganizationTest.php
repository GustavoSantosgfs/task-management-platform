<?php

namespace Tests\Unit\Models;

use App\Models\Organization;
use App\Models\OrganizationUser;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrganizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_has_many_projects(): void
    {
        $organization = Organization::factory()->create();
        $project = Project::factory()->create(['organization_id' => $organization->id]);

        $this->assertCount(1, $organization->projects);
        $this->assertEquals($project->id, $organization->projects->first()->id);
    }

    public function test_has_many_organization_users(): void
    {
        $organization = Organization::factory()->create();
        $user = User::factory()->create();
        OrganizationUser::factory()->create([
            'organization_id' => $organization->id,
            'user_id' => $user->id,
        ]);

        $this->assertCount(1, $organization->organizationUsers);
    }

    public function test_belongs_to_many_users(): void
    {
        $organization = Organization::factory()->create();
        $user = User::factory()->create();
        OrganizationUser::factory()->create([
            'organization_id' => $organization->id,
            'user_id' => $user->id,
            'role' => 'member',
        ]);

        $this->assertCount(1, $organization->users);
        $this->assertEquals($user->id, $organization->users->first()->id);
    }

    public function test_admins_returns_only_admin_users(): void
    {
        $organization = Organization::factory()->create();
        $admin = User::factory()->create();
        $member = User::factory()->create();

        OrganizationUser::factory()->create([
            'organization_id' => $organization->id,
            'user_id' => $admin->id,
            'role' => 'admin',
        ]);
        OrganizationUser::factory()->create([
            'organization_id' => $organization->id,
            'user_id' => $member->id,
            'role' => 'member',
        ]);

        $this->assertCount(1, $organization->admins);
        $this->assertEquals($admin->id, $organization->admins->first()->id);
    }

    public function test_managers_returns_admin_and_project_manager_users(): void
    {
        $organization = Organization::factory()->create();
        $admin = User::factory()->create();
        $manager = User::factory()->create();
        $member = User::factory()->create();

        OrganizationUser::factory()->create([
            'organization_id' => $organization->id,
            'user_id' => $admin->id,
            'role' => 'admin',
        ]);
        OrganizationUser::factory()->create([
            'organization_id' => $organization->id,
            'user_id' => $manager->id,
            'role' => 'project_manager',
        ]);
        OrganizationUser::factory()->create([
            'organization_id' => $organization->id,
            'user_id' => $member->id,
            'role' => 'member',
        ]);

        $this->assertCount(2, $organization->managers);
    }

    public function test_members_returns_only_member_users(): void
    {
        $organization = Organization::factory()->create();
        $admin = User::factory()->create();
        $member = User::factory()->create();

        OrganizationUser::factory()->create([
            'organization_id' => $organization->id,
            'user_id' => $admin->id,
            'role' => 'admin',
        ]);
        OrganizationUser::factory()->create([
            'organization_id' => $organization->id,
            'user_id' => $member->id,
            'role' => 'member',
        ]);

        $this->assertCount(1, $organization->members);
        $this->assertEquals($member->id, $organization->members->first()->id);
    }

    public function test_soft_deletes(): void
    {
        $organization = Organization::factory()->create();
        $organization->delete();

        $this->assertSoftDeleted($organization);
        $this->assertNotNull(Organization::withTrashed()->find($organization->id));
    }
}
