<?php

namespace Tests\Unit\Models;

use App\Models\Organization;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectMemberTest extends TestCase
{
    use RefreshDatabase;

    public function test_belongs_to_project(): void
    {
        $organization = Organization::factory()->create();
        $project = Project::factory()->create(['organization_id' => $organization->id]);
        $user = User::factory()->create();

        $projectMember = ProjectMember::create([
            'project_id' => $project->id,
            'user_id' => $user->id,
        ]);

        $this->assertInstanceOf(Project::class, $projectMember->project);
        $this->assertEquals($project->id, $projectMember->project->id);
    }

    public function test_belongs_to_user(): void
    {
        $organization = Organization::factory()->create();
        $project = Project::factory()->create(['organization_id' => $organization->id]);
        $user = User::factory()->create();

        $projectMember = ProjectMember::create([
            'project_id' => $project->id,
            'user_id' => $user->id,
        ]);

        $this->assertInstanceOf(User::class, $projectMember->user);
        $this->assertEquals($user->id, $projectMember->user->id);
    }

    public function test_fillable_attributes(): void
    {
        $organization = Organization::factory()->create();
        $project = Project::factory()->create(['organization_id' => $organization->id]);
        $user = User::factory()->create();

        $projectMember = ProjectMember::create([
            'project_id' => $project->id,
            'user_id' => $user->id,
        ]);

        $this->assertEquals($project->id, $projectMember->project_id);
        $this->assertEquals($user->id, $projectMember->user_id);
    }
}
