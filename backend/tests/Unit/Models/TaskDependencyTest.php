<?php

namespace Tests\Unit\Models;

use App\Models\Organization;
use App\Models\Project;
use App\Models\Task;
use App\Models\TaskDependency;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskDependencyTest extends TestCase
{
    use RefreshDatabase;

    public function test_belongs_to_task(): void
    {
        $organization = Organization::factory()->create();
        $project = Project::factory()->create(['organization_id' => $organization->id]);
        $task1 = Task::factory()->create(['project_id' => $project->id]);
        $task2 = Task::factory()->create(['project_id' => $project->id]);

        $dependency = TaskDependency::create([
            'task_id' => $task1->id,
            'depends_on_task_id' => $task2->id,
        ]);

        $this->assertInstanceOf(Task::class, $dependency->task);
        $this->assertEquals($task1->id, $dependency->task->id);
    }

    public function test_belongs_to_depends_on_task(): void
    {
        $organization = Organization::factory()->create();
        $project = Project::factory()->create(['organization_id' => $organization->id]);
        $task1 = Task::factory()->create(['project_id' => $project->id]);
        $task2 = Task::factory()->create(['project_id' => $project->id]);

        $dependency = TaskDependency::create([
            'task_id' => $task1->id,
            'depends_on_task_id' => $task2->id,
        ]);

        $this->assertInstanceOf(Task::class, $dependency->dependsOnTask);
        $this->assertEquals($task2->id, $dependency->dependsOnTask->id);
    }

    public function test_fillable_attributes(): void
    {
        $organization = Organization::factory()->create();
        $project = Project::factory()->create(['organization_id' => $organization->id]);
        $task1 = Task::factory()->create(['project_id' => $project->id]);
        $task2 = Task::factory()->create(['project_id' => $project->id]);

        $dependency = TaskDependency::create([
            'task_id' => $task1->id,
            'depends_on_task_id' => $task2->id,
        ]);

        $this->assertEquals($task1->id, $dependency->task_id);
        $this->assertEquals($task2->id, $dependency->depends_on_task_id);
    }
}
