<?php

namespace Database\Seeders;

use App\Models\Project;
use App\Models\ProjectMember;
use Illuminate\Database\Seeder;

class ProjectSeeder extends Seeder
{
    public function run(): void
    {
        // Project 1: Website Redesign (Public)
        $project1 = Project::create([
            'organization_id' => 1,
            'manager_id' => 2,
            'created_by' => 1,
            'title' => 'Website Redesign',
            'description' => 'Complete redesign of the company website with modern UI/UX.',
            'start_date' => now(),
            'end_date' => now()->addMonths(3),
            'status' => 'active',
            'visibility' => 'public',
        ]);

        // Add members to project 1
        ProjectMember::create(['project_id' => $project1->id, 'user_id' => 2]);
        ProjectMember::create(['project_id' => $project1->id, 'user_id' => 3]);
        ProjectMember::create(['project_id' => $project1->id, 'user_id' => 4]);

        // Project 2: Mobile App Development (Private)
        $project2 = Project::create([
            'organization_id' => 1,
            'manager_id' => 2,
            'created_by' => 1,
            'title' => 'Mobile App Development',
            'description' => 'Develop a cross-platform mobile application for client engagement.',
            'start_date' => now()->subWeeks(2),
            'end_date' => now()->addMonths(6),
            'status' => 'active',
            'visibility' => 'private',
        ]);

        // Add members to project 2
        ProjectMember::create(['project_id' => $project2->id, 'user_id' => 2]);
        ProjectMember::create(['project_id' => $project2->id, 'user_id' => 3]);

        // Project 3: API Integration (Planning)
        $project3 = Project::create([
            'organization_id' => 1,
            'manager_id' => 2,
            'created_by' => 2,
            'title' => 'API Integration Project',
            'description' => 'Integrate third-party APIs for payment and authentication.',
            'start_date' => now()->addWeeks(2),
            'end_date' => now()->addMonths(2),
            'status' => 'planning',
            'visibility' => 'public',
        ]);

        ProjectMember::create(['project_id' => $project3->id, 'user_id' => 2]);
        ProjectMember::create(['project_id' => $project3->id, 'user_id' => 4]);

        // Project 4: Legacy System Migration (On Hold)
        $project4 = Project::create([
            'organization_id' => 1,
            'manager_id' => 2,
            'created_by' => 1,
            'title' => 'Legacy System Migration',
            'description' => 'Migrate legacy systems to modern cloud infrastructure.',
            'start_date' => now()->subMonths(1),
            'end_date' => now()->addMonths(4),
            'status' => 'on_hold',
            'visibility' => 'public',
        ]);

        ProjectMember::create(['project_id' => $project4->id, 'user_id' => 2]);
        ProjectMember::create(['project_id' => $project4->id, 'user_id' => 3]);
        ProjectMember::create(['project_id' => $project4->id, 'user_id' => 4]);
    }
}
