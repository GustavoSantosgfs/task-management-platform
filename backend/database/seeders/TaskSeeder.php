<?php

namespace Database\Seeders;

use App\Models\Task;
use App\Models\TaskComment;
use App\Models\TaskDependency;
use Illuminate\Database\Seeder;

class TaskSeeder extends Seeder
{
    public function run(): void
    {
        // Tasks for Project 1: Website Redesign
        $task1 = Task::create([
            'project_id' => 1,
            'assignee_id' => 3,
            'created_by' => 2,
            'title' => 'Design Homepage Mockup',
            'description' => 'Create initial mockup designs for the new homepage.',
            'priority' => 'high',
            'status' => 'done',
            'due_date' => now()->subDays(5),
            'position' => 0,
        ]);

        $task2 = Task::create([
            'project_id' => 1,
            'assignee_id' => 3,
            'created_by' => 2,
            'title' => 'Implement Homepage UI',
            'description' => 'Convert the approved mockup to HTML/CSS/JS.',
            'priority' => 'high',
            'status' => 'in_progress',
            'due_date' => now()->addDays(3),
            'position' => 1,
        ]);

        // Task 2 depends on Task 1
        TaskDependency::create([
            'task_id' => $task2->id,
            'depends_on_task_id' => $task1->id,
        ]);

        $task3 = Task::create([
            'project_id' => 1,
            'assignee_id' => 4,
            'created_by' => 2,
            'title' => 'Setup Backend API',
            'description' => 'Create REST API endpoints for the website.',
            'priority' => 'medium',
            'status' => 'review',
            'due_date' => now()->addDays(5),
            'position' => 2,
        ]);

        Task::create([
            'project_id' => 1,
            'assignee_id' => null,
            'created_by' => 2,
            'title' => 'Write Unit Tests',
            'description' => 'Write comprehensive unit tests for all components.',
            'priority' => 'medium',
            'status' => 'backlog',
            'due_date' => now()->addWeeks(2),
            'position' => 3,
        ]);

        Task::create([
            'project_id' => 1,
            'assignee_id' => 3,
            'created_by' => 2,
            'title' => 'Performance Optimization',
            'description' => 'Optimize page load times and overall performance.',
            'priority' => 'low',
            'status' => 'todo',
            'due_date' => now()->addWeeks(3),
            'position' => 4,
        ]);

        // Tasks for Project 2: Mobile App
        Task::create([
            'project_id' => 2,
            'assignee_id' => 3,
            'created_by' => 2,
            'title' => 'Setup React Native Project',
            'description' => 'Initialize the React Native project with required dependencies.',
            'priority' => 'critical',
            'status' => 'done',
            'due_date' => now()->subDays(10),
            'position' => 0,
        ]);

        Task::create([
            'project_id' => 2,
            'assignee_id' => 3,
            'created_by' => 2,
            'title' => 'Design App Navigation',
            'description' => 'Implement bottom tab navigation and stack navigators.',
            'priority' => 'high',
            'status' => 'in_progress',
            'due_date' => now()->addDays(2),
            'position' => 1,
        ]);

        Task::create([
            'project_id' => 2,
            'assignee_id' => null,
            'created_by' => 2,
            'title' => 'Implement Authentication Flow',
            'description' => 'Create login, register, and forgot password screens.',
            'priority' => 'high',
            'status' => 'todo',
            'due_date' => now()->addWeeks(1),
            'position' => 2,
        ]);

        $blockedTask = Task::create([
            'project_id' => 2,
            'assignee_id' => 3,
            'created_by' => 2,
            'title' => 'API Integration',
            'description' => 'Connect the app to backend APIs.',
            'priority' => 'high',
            'status' => 'blocked',
            'due_date' => now()->addWeeks(2),
            'position' => 3,
        ]);

        // Add comments to tasks
        TaskComment::create([
            'task_id' => $task2->id,
            'user_id' => 2,
            'content' => 'Please make sure to follow the brand guidelines for colors and fonts.',
            'mentions' => [3],
        ]);

        TaskComment::create([
            'task_id' => $task2->id,
            'user_id' => 3,
            'content' => 'Got it! I\'ll have the first version ready by tomorrow.',
            'mentions' => null,
        ]);

        TaskComment::create([
            'task_id' => $task3->id,
            'user_id' => 4,
            'content' => 'API documentation is ready for review.',
            'mentions' => [2],
        ]);

        TaskComment::create([
            'task_id' => $blockedTask->id,
            'user_id' => 3,
            'content' => 'Blocked waiting for the backend API to be completed.',
            'mentions' => [2, 4],
        ]);
    }
}
