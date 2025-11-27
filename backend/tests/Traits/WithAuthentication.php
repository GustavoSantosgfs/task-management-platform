<?php

namespace Tests\Traits;

use App\Models\Organization;
use App\Models\OrganizationUser;
use App\Models\Project;
use App\Models\User;
use App\Services\JwtService;

trait WithAuthentication
{
    protected ?Organization $organization = null;

    protected function createOrganization(array $attributes = []): Organization
    {
        $this->organization = Organization::factory()->create($attributes);

        return $this->organization;
    }

    protected function createUserWithRole(string $role = 'member', ?Organization $organization = null): User
    {
        $org = $organization ?? $this->organization ?? $this->createOrganization();

        $user = User::factory()->create();

        OrganizationUser::factory()->create([
            'organization_id' => $org->id,
            'user_id' => $user->id,
            'role' => $role,
        ]);

        return $user;
    }

    protected function createAdmin(?Organization $organization = null): User
    {
        return $this->createUserWithRole('admin', $organization);
    }

    protected function createProjectManager(?Organization $organization = null): User
    {
        return $this->createUserWithRole('project_manager', $organization);
    }

    protected function createMember(?Organization $organization = null): User
    {
        return $this->createUserWithRole('member', $organization);
    }

    protected function getTokenForUser(User $user): string
    {
        $jwtService = app(JwtService::class);
        $orgUser = $user->organizationUsers()->first();

        return $jwtService->createTokenForUser([
            'id' => $user->id,
            'email' => $user->email,
            'role' => $orgUser?->role ?? 'member',
            'orgId' => $orgUser?->organization_id ?? 1,
        ]);
    }

    protected function actingAsUser(User $user): self
    {
        $token = $this->getTokenForUser($user);

        return $this->withHeader('Authorization', "Bearer {$token}");
    }

    protected function createProjectForOrganization(?Organization $organization = null, array $attributes = []): Project
    {
        $org = $organization ?? $this->organization ?? $this->createOrganization();
        $manager = $this->createProjectManager($org);

        return Project::factory()->create(array_merge([
            'organization_id' => $org->id,
            'manager_id' => $manager->id,
            'created_by' => $manager->id,
        ], $attributes));
    }
}
