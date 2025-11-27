<?php

namespace Tests\Unit\Models;

use App\Models\Organization;
use App\Models\OrganizationUser;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrganizationUserTest extends TestCase
{
    use RefreshDatabase;

    public function test_is_admin_returns_true_for_admin_role(): void
    {
        $orgUser = new OrganizationUser(['role' => 'admin']);

        $this->assertTrue($orgUser->isAdmin());
    }

    public function test_is_admin_returns_false_for_non_admin_role(): void
    {
        $orgUser = new OrganizationUser(['role' => 'member']);

        $this->assertFalse($orgUser->isAdmin());
    }

    public function test_is_manager_returns_true_for_admin_role(): void
    {
        $orgUser = new OrganizationUser(['role' => 'admin']);

        $this->assertTrue($orgUser->isManager());
    }

    public function test_is_manager_returns_true_for_project_manager_role(): void
    {
        $orgUser = new OrganizationUser(['role' => 'project_manager']);

        $this->assertTrue($orgUser->isManager());
    }

    public function test_is_manager_returns_false_for_member_role(): void
    {
        $orgUser = new OrganizationUser(['role' => 'member']);

        $this->assertFalse($orgUser->isManager());
    }

    public function test_is_member_returns_true_for_member_role(): void
    {
        $orgUser = new OrganizationUser(['role' => 'member']);

        $this->assertTrue($orgUser->isMember());
    }

    public function test_is_member_returns_false_for_admin_role(): void
    {
        $orgUser = new OrganizationUser(['role' => 'admin']);

        $this->assertFalse($orgUser->isMember());
    }

    public function test_belongs_to_organization(): void
    {
        $organization = Organization::factory()->create();
        $user = User::factory()->create();
        $orgUser = OrganizationUser::factory()->create([
            'organization_id' => $organization->id,
            'user_id' => $user->id,
        ]);

        $this->assertInstanceOf(Organization::class, $orgUser->organization);
        $this->assertEquals($organization->id, $orgUser->organization->id);
    }

    public function test_belongs_to_user(): void
    {
        $organization = Organization::factory()->create();
        $user = User::factory()->create();
        $orgUser = OrganizationUser::factory()->create([
            'organization_id' => $organization->id,
            'user_id' => $user->id,
        ]);

        $this->assertInstanceOf(User::class, $orgUser->user);
        $this->assertEquals($user->id, $orgUser->user->id);
    }
}
