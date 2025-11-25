<?php

namespace App\Services;

use App\Models\User;
use App\Models\OrganizationUser;

class AuthService
{
    private JwtService $jwtService;
    private array $mockUsers;

    public function __construct(JwtService $jwtService)
    {
        $this->jwtService = $jwtService;
        $this->mockUsers = config('mock_users.users', []);
    }

    public function attemptLogin(string $email, string $password): ?array
    {
        $mockUser = $this->findMockUserByEmail($email);

        if (!$mockUser || $mockUser['password'] !== $password) {
            return null;
        }

        $this->syncUserToDatabase($mockUser);

        $token = $this->jwtService->createTokenForUser($mockUser);

        return [
            'token' => $token,
            'user' => $this->formatUserResponse($mockUser),
        ];
    }

    public function getUserFromToken(string $token): ?array
    {
        $payload = $this->jwtService->validateToken($token);

        if (!$payload) {
            return null;
        }

        $mockUser = $this->findMockUserById($payload['sub']);

        if (!$mockUser) {
            return null;
        }

        return $this->formatUserResponse($mockUser);
    }

    public function getFullUserFromToken(string $token): ?array
    {
        $payload = $this->jwtService->validateToken($token);

        if (!$payload) {
            return null;
        }

        return $this->findMockUserById($payload['sub']);
    }

    public function getTokenPayload(string $token): ?array
    {
        return $this->jwtService->validateToken($token);
    }

    private function findMockUserByEmail(string $email): ?array
    {
        foreach ($this->mockUsers as $user) {
            if ($user['email'] === $email) {
                return $user;
            }
        }
        return null;
    }

    private function findMockUserById(int $id): ?array
    {
        foreach ($this->mockUsers as $user) {
            if ($user['id'] === $id) {
                return $user;
            }
        }
        return null;
    }

    private function syncUserToDatabase(array $mockUser): void
    {
        $user = User::updateOrCreate(
            ['email' => $mockUser['email']],
            [
                'id' => $mockUser['id'],
                'name' => $mockUser['name'],
                'avatar' => $mockUser['avatar'],
            ]
        );

        OrganizationUser::updateOrCreate(
            [
                'organization_id' => $mockUser['orgId'],
                'user_id' => $user->id,
            ],
            [
                'role' => $mockUser['role'],
            ]
        );
    }

    private function formatUserResponse(array $mockUser): array
    {
        return [
            'id' => $mockUser['id'],
            'name' => $mockUser['name'],
            'email' => $mockUser['email'],
            'role' => $mockUser['role'],
            'orgId' => $mockUser['orgId'],
            'avatar' => $mockUser['avatar'],
        ];
    }

    public function getAllMockUsers(): array
    {
        return array_map(function ($user) {
            return $this->formatUserResponse($user);
        }, $this->mockUsers);
    }
}
