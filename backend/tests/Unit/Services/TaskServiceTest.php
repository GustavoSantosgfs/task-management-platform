<?php

namespace Tests\Unit\Services;

use App\Models\Project;
use App\Models\Task;
use App\Models\TaskComment;
use App\Repositories\Interfaces\ProjectRepositoryInterface;
use App\Repositories\Interfaces\TaskRepositoryInterface;
use App\Services\TaskService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Pagination\LengthAwarePaginator;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

class TaskServiceTest extends TestCase
{
    use RefreshDatabase;

    private TaskService $taskService;
    private MockInterface $taskRepository;
    private MockInterface $projectRepository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->taskRepository = Mockery::mock(TaskRepositoryInterface::class);
        $this->projectRepository = Mockery::mock(ProjectRepositoryInterface::class);
        $this->taskService = new TaskService($this->taskRepository, $this->projectRepository);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    // canManageTask Tests

    public function test_admin_can_manage_task(): void
    {
        $this->assertTrue($this->taskService->canManageTask('admin'));
    }

    public function test_project_manager_can_manage_task(): void
    {
        $this->assertTrue($this->taskService->canManageTask('project_manager'));
    }

    public function test_member_cannot_manage_task(): void
    {
        $this->assertFalse($this->taskService->canManageTask('member'));
    }

    // getTasks Tests

    public function test_get_tasks_returns_null_when_project_not_found(): void
    {
        $this->projectRepository->shouldReceive('findByOrganization')
            ->with(1, 999)
            ->once()
            ->andReturn(null);

        $result = $this->taskService->getTasks(1, 999, 1, 'admin');

        $this->assertNull($result);
    }

    public function test_get_tasks_returns_null_for_unauthorized_member_on_private_project(): void
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

        $result = $this->taskService->getTasks(1, 1, 2, 'member');

        $this->assertNull($result);
    }

    public function test_get_tasks_returns_tasks_for_authorized_user(): void
    {
        $project = new Project(['visibility' => 'public']);
        $project->id = 1;

        $paginator = new LengthAwarePaginator([], 0, 20);

        $this->projectRepository->shouldReceive('findByOrganization')
            ->with(1, 1)
            ->once()
            ->andReturn($project);

        $this->taskRepository->shouldReceive('getByProject')
            ->with(1, [], 20)
            ->once()
            ->andReturn($paginator);

        $result = $this->taskService->getTasks(1, 1, 1, 'member');

        $this->assertInstanceOf(LengthAwarePaginator::class, $result);
    }

    // getTask Tests

    public function test_get_task_returns_null_when_project_not_found(): void
    {
        $this->projectRepository->shouldReceive('findByOrganization')
            ->with(1, 999)
            ->once()
            ->andReturn(null);

        $result = $this->taskService->getTask(1, 999, 1, 'admin', 1);

        $this->assertNull($result);
    }

    // getMyTasks Tests

    public function test_get_my_tasks_calls_repository(): void
    {
        $paginator = new LengthAwarePaginator([], 0, 20);

        $this->taskRepository->shouldReceive('getTasksByAssignee')
            ->with(1, ['status' => 'todo'], 10)
            ->once()
            ->andReturn($paginator);

        $result = $this->taskService->getMyTasks(1, ['status' => 'todo'], 10);

        $this->assertInstanceOf(LengthAwarePaginator::class, $result);
    }

    // createTask Tests

    public function test_create_task_returns_null_when_project_not_found(): void
    {
        $this->projectRepository->shouldReceive('findByOrganization')
            ->with(1, 999)
            ->once()
            ->andReturn(null);

        $result = $this->taskService->createTask(1, 999, 1, ['title' => 'Test']);

        $this->assertNull($result);
    }

    // updateTask Tests

    public function test_update_task_returns_null_when_project_not_found(): void
    {
        $this->projectRepository->shouldReceive('findByOrganization')
            ->with(1, 999)
            ->once()
            ->andReturn(null);

        $result = $this->taskService->updateTask(1, 999, 1, 1, ['title' => 'Updated']);

        $this->assertNull($result);
    }

    public function test_update_task_returns_null_when_task_not_found(): void
    {
        $project = new Project();
        $project->id = 1;

        $this->projectRepository->shouldReceive('findByOrganization')
            ->with(1, 1)
            ->once()
            ->andReturn($project);

        $this->taskRepository->shouldReceive('findByProject')
            ->with(1, 999)
            ->once()
            ->andReturn(null);

        $result = $this->taskService->updateTask(1, 1, 1, 999, ['title' => 'Updated']);

        $this->assertNull($result);
    }

    // deleteTask Tests

    public function test_delete_task_returns_false_when_project_not_found(): void
    {
        $this->projectRepository->shouldReceive('findByOrganization')
            ->with(1, 999)
            ->once()
            ->andReturn(null);

        $result = $this->taskService->deleteTask(1, 999, 1, 1);

        $this->assertFalse($result);
    }

    public function test_delete_task_returns_false_when_task_not_found(): void
    {
        $project = new Project();
        $project->id = 1;

        $this->projectRepository->shouldReceive('findByOrganization')
            ->with(1, 1)
            ->once()
            ->andReturn($project);

        $this->taskRepository->shouldReceive('findByProject')
            ->with(1, 999)
            ->once()
            ->andReturn(null);

        $result = $this->taskService->deleteTask(1, 1, 1, 999);

        $this->assertFalse($result);
    }

    // addDependency Tests

    public function test_add_dependency_returns_not_found_when_project_missing(): void
    {
        $this->projectRepository->shouldReceive('findByOrganization')
            ->with(1, 999)
            ->once()
            ->andReturn(null);

        $result = $this->taskService->addDependency(1, 999, 1, 1, 2);

        $this->assertFalse($result['success']);
        $this->assertEquals('NOT_FOUND', $result['code']);
    }

    public function test_add_dependency_returns_not_found_when_task_missing(): void
    {
        $project = new Project();
        $project->id = 1;

        $this->projectRepository->shouldReceive('findByOrganization')
            ->with(1, 1)
            ->once()
            ->andReturn($project);

        $this->taskRepository->shouldReceive('findByProject')
            ->with(1, 999)
            ->once()
            ->andReturn(null);

        $this->taskRepository->shouldReceive('findByProject')
            ->with(1, 2)
            ->once()
            ->andReturn(new Task());

        $result = $this->taskService->addDependency(1, 1, 1, 999, 2);

        $this->assertFalse($result['success']);
        $this->assertEquals('NOT_FOUND', $result['code']);
    }

    public function test_add_dependency_prevents_self_reference(): void
    {
        $project = new Project();
        $project->id = 1;

        $task = new Task();
        $task->id = 1;

        $this->projectRepository->shouldReceive('findByOrganization')
            ->with(1, 1)
            ->once()
            ->andReturn($project);

        $this->taskRepository->shouldReceive('findByProject')
            ->with(1, 1)
            ->times(2)
            ->andReturn($task);

        $result = $this->taskService->addDependency(1, 1, 1, 1, 1);

        $this->assertFalse($result['success']);
        $this->assertEquals('INVALID_DEPENDENCY', $result['code']);
        $this->assertEquals('A task cannot depend on itself', $result['error']);
    }

    public function test_add_dependency_returns_error_when_already_exists(): void
    {
        $project = new Project();
        $project->id = 1;

        $task1 = new Task();
        $task1->id = 1;

        $task2 = new Task();
        $task2->id = 2;

        $existingDependencies = new Collection([$task2]);

        $this->projectRepository->shouldReceive('findByOrganization')
            ->with(1, 1)
            ->once()
            ->andReturn($project);

        $this->taskRepository->shouldReceive('findByProject')
            ->with(1, 1)
            ->once()
            ->andReturn($task1);

        $this->taskRepository->shouldReceive('findByProject')
            ->with(1, 2)
            ->once()
            ->andReturn($task2);

        $this->taskRepository->shouldReceive('getDependencies')
            ->with(1)
            ->once()
            ->andReturn($existingDependencies);

        $result = $this->taskService->addDependency(1, 1, 1, 1, 2);

        $this->assertFalse($result['success']);
        $this->assertEquals('ALREADY_EXISTS', $result['code']);
    }

    // removeDependency Tests

    public function test_remove_dependency_returns_not_found_when_project_missing(): void
    {
        $this->projectRepository->shouldReceive('findByOrganization')
            ->with(1, 999)
            ->once()
            ->andReturn(null);

        $result = $this->taskService->removeDependency(1, 999, 1, 1, 2);

        $this->assertFalse($result['success']);
        $this->assertEquals('NOT_FOUND', $result['code']);
    }

    public function test_remove_dependency_returns_not_found_when_task_missing(): void
    {
        $project = new Project();
        $project->id = 1;

        $this->projectRepository->shouldReceive('findByOrganization')
            ->with(1, 1)
            ->once()
            ->andReturn($project);

        $this->taskRepository->shouldReceive('findByProject')
            ->with(1, 999)
            ->once()
            ->andReturn(null);

        $result = $this->taskService->removeDependency(1, 1, 1, 999, 2);

        $this->assertFalse($result['success']);
        $this->assertEquals('NOT_FOUND', $result['code']);
    }

    // getDependencies Tests

    public function test_get_dependencies_returns_null_when_task_not_found(): void
    {
        $this->taskRepository->shouldReceive('findByProject')
            ->with(1, 999)
            ->once()
            ->andReturn(null);

        $result = $this->taskService->getDependencies(1, 999);

        $this->assertNull($result);
    }

    public function test_get_dependencies_returns_collection(): void
    {
        $task = new Task();
        $task->id = 1;

        $dependencies = new Collection([new Task(), new Task()]);

        $this->taskRepository->shouldReceive('findByProject')
            ->with(1, 1)
            ->once()
            ->andReturn($task);

        $this->taskRepository->shouldReceive('getDependencies')
            ->with(1)
            ->once()
            ->andReturn($dependencies);

        $result = $this->taskService->getDependencies(1, 1);

        $this->assertInstanceOf(Collection::class, $result);
        $this->assertCount(2, $result);
    }

    // getComments Tests

    public function test_get_comments_returns_null_when_task_not_found(): void
    {
        $this->taskRepository->shouldReceive('findByProject')
            ->with(1, 999)
            ->once()
            ->andReturn(null);

        $result = $this->taskService->getComments(1, 999);

        $this->assertNull($result);
    }

    public function test_get_comments_returns_collection(): void
    {
        $task = new Task();
        $task->id = 1;

        $comments = new Collection([new TaskComment(), new TaskComment()]);

        $this->taskRepository->shouldReceive('findByProject')
            ->with(1, 1)
            ->once()
            ->andReturn($task);

        $this->taskRepository->shouldReceive('getComments')
            ->with(1)
            ->once()
            ->andReturn($comments);

        $result = $this->taskService->getComments(1, 1);

        $this->assertInstanceOf(Collection::class, $result);
        $this->assertCount(2, $result);
    }

    // addComment Tests

    public function test_add_comment_returns_not_found_when_project_missing(): void
    {
        $this->projectRepository->shouldReceive('findByOrganization')
            ->with(1, 999)
            ->once()
            ->andReturn(null);

        $result = $this->taskService->addComment(1, 999, 1, 1, 'Test comment');

        $this->assertFalse($result['success']);
        $this->assertEquals('NOT_FOUND', $result['code']);
    }

    public function test_add_comment_returns_not_found_when_task_missing(): void
    {
        $project = new Project();
        $project->id = 1;

        $this->projectRepository->shouldReceive('findByOrganization')
            ->with(1, 1)
            ->once()
            ->andReturn($project);

        $this->taskRepository->shouldReceive('findByProject')
            ->with(1, 999)
            ->once()
            ->andReturn(null);

        $result = $this->taskService->addComment(1, 1, 1, 999, 'Test comment');

        $this->assertFalse($result['success']);
        $this->assertEquals('NOT_FOUND', $result['code']);
    }

    // updateComment Tests

    public function test_update_comment_returns_not_found_when_project_missing(): void
    {
        $this->projectRepository->shouldReceive('findByOrganization')
            ->with(1, 999)
            ->once()
            ->andReturn(null);

        $result = $this->taskService->updateComment(1, 999, 1, 1, 1, 'Updated');

        $this->assertFalse($result['success']);
        $this->assertEquals('NOT_FOUND', $result['code']);
    }

    public function test_update_comment_returns_forbidden_for_non_author(): void
    {
        $project = new Project();
        $project->id = 1;

        $task = new Task();
        $task->id = 1;

        $comment = new TaskComment(['user_id' => 5, 'task_id' => 1]);
        $comment->id = 1;

        $this->projectRepository->shouldReceive('findByOrganization')
            ->with(1, 1)
            ->once()
            ->andReturn($project);

        $this->taskRepository->shouldReceive('findByProject')
            ->with(1, 1)
            ->once()
            ->andReturn($task);

        $this->taskRepository->shouldReceive('findComment')
            ->with(1)
            ->once()
            ->andReturn($comment);

        $result = $this->taskService->updateComment(1, 1, 2, 1, 1, 'Updated');

        $this->assertFalse($result['success']);
        $this->assertEquals('FORBIDDEN', $result['code']);
        $this->assertEquals('You can only edit your own comments', $result['error']);
    }

    // deleteComment Tests

    public function test_delete_comment_returns_not_found_when_project_missing(): void
    {
        $this->projectRepository->shouldReceive('findByOrganization')
            ->with(1, 999)
            ->once()
            ->andReturn(null);

        $result = $this->taskService->deleteComment(1, 999, 1, 'admin', 1, 1);

        $this->assertFalse($result['success']);
        $this->assertEquals('NOT_FOUND', $result['code']);
    }

    public function test_delete_comment_forbidden_for_non_author_member(): void
    {
        $project = new Project();
        $project->id = 1;

        $task = new Task();
        $task->id = 1;

        $comment = new TaskComment(['user_id' => 5, 'task_id' => 1]);
        $comment->id = 1;

        $this->projectRepository->shouldReceive('findByOrganization')
            ->with(1, 1)
            ->once()
            ->andReturn($project);

        $this->taskRepository->shouldReceive('findByProject')
            ->with(1, 1)
            ->once()
            ->andReturn($task);

        $this->taskRepository->shouldReceive('findComment')
            ->with(1)
            ->once()
            ->andReturn($comment);

        $result = $this->taskService->deleteComment(1, 1, 2, 'member', 1, 1);

        $this->assertFalse($result['success']);
        $this->assertEquals('FORBIDDEN', $result['code']);
    }

    // Note: Tests for admin/manager deleting comments are covered in integration tests
    // as they involve activity logging which requires database setup
}

