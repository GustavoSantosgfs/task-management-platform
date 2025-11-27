<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\AuthService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    use ApiResponse;

    public function __construct(
        private AuthService $authService
    ) {}

    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $result = $this->authService->attemptLogin(
            $validated['email'],
            $validated['password']
        );

        if (!$result) {
            return $this->errorResponse(
                'Invalid credentials',
                'INVALID_CREDENTIALS',
                401
            );
        }

        return $this->successResponse($result, 'Login successful');
    }

    public function me(Request $request): JsonResponse
    {
        $user = $request->attributes->get('auth_user');

        if (!$user) {
            return $this->unauthorizedResponse();
        }

        return $this->successResponse($user, 'User retrieved successfully');
    }

    public function logout(): JsonResponse
    {
        return $this->successResponse(null, 'Logout successful');
    }

    public function mockUsers(): JsonResponse
    {
        $users = $this->authService->getAllMockUsers();

        return $this->successResponse($users, 'Mock users retrieved successfully');
    }
}
