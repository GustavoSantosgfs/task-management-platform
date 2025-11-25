<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Mock Users for Authentication
    |--------------------------------------------------------------------------
    |
    | These users are used for the mocked authentication flow.
    | Each user has: id, name, email, password, role, and orgId
    |
    | Roles: member, project_manager, admin
    |
    */

    'users' => [
        [
            'id' => 1,
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => 'password123',
            'role' => 'admin',
            'orgId' => 1,
            'avatar' => null,
        ],
        [
            'id' => 2,
            'name' => 'Project Manager',
            'email' => 'manager@example.com',
            'password' => 'password123',
            'role' => 'project_manager',
            'orgId' => 1,
            'avatar' => null,
        ],
        [
            'id' => 3,
            'name' => 'Team Member',
            'email' => 'member@example.com',
            'password' => 'password123',
            'role' => 'member',
            'orgId' => 1,
            'avatar' => null,
        ],
        [
            'id' => 4,
            'name' => 'Another Member',
            'email' => 'member2@example.com',
            'password' => 'password123',
            'role' => 'member',
            'orgId' => 1,
            'avatar' => null,
        ],
        [
            'id' => 5,
            'name' => 'Org 2 Admin',
            'email' => 'admin2@example.com',
            'password' => 'password123',
            'role' => 'admin',
            'orgId' => 2,
            'avatar' => null,
        ],
    ],
];
