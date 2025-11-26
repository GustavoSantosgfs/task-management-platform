<?php

namespace Database\Seeders;

use App\Models\Organization;
use Illuminate\Database\Seeder;

class OrganizationSeeder extends Seeder
{
    public function run(): void
    {
        Organization::create([
            'id' => 1,
            'name' => 'Acme Digital Agency',
            'slug' => 'acme-digital-agency',
            'description' => 'A leading digital agency specializing in web development and design.',
        ]);

        Organization::create([
            'id' => 2,
            'name' => 'Tech Solutions Inc',
            'slug' => 'tech-solutions-inc',
            'description' => 'Enterprise software solutions provider.',
        ]);
    }
}
