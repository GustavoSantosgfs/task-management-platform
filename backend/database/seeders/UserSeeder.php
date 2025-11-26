<?php

namespace Database\Seeders;

use App\Models\OrganizationUser;
use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $mockUsers = config('mock_users.users', []);

        foreach ($mockUsers as $mockUser) {
            $user = User::create([
                'id' => $mockUser['id'],
                'name' => $mockUser['name'],
                'email' => $mockUser['email'],
                'avatar' => $mockUser['avatar'],
            ]);

            OrganizationUser::create([
                'organization_id' => $mockUser['orgId'],
                'user_id' => $user->id,
                'role' => $mockUser['role'],
            ]);
        }
    }
}
