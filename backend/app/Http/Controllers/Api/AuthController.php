<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\AuthService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class AuthController extends Controller
{
    use ApiResponse;

    public function __construct(
        private AuthService $authService
    ) {}

    #[OA\Post(
        path: '/auth/login',
        summary: 'Login to the application',
        description: 'Authenticate with email and password to receive a JWT token',
        tags: ['Authentication'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['email', 'password'],
                properties: [
                    new OA\Property(property: 'email', type: 'string', format: 'email', example: 'admin@example.com'),
                    new OA\Property(property: 'password', type: 'string', example: 'password123')
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Login successful',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'data', type: 'object', properties: [
                            new OA\Property(property: 'token', type: 'string', example: 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...'),
                            new OA\Property(property: 'user', type: 'object', properties: [
                                new OA\Property(property: 'id', type: 'integer', example: 1),
                                new OA\Property(property: 'name', type: 'string', example: 'Admin User'),
                                new OA\Property(property: 'email', type: 'string', example: 'admin@example.com'),
                                new OA\Property(property: 'role', type: 'string', example: 'admin'),
                                new OA\Property(property: 'orgId', type: 'integer', example: 1)
                            ])
                        ]),
                        new OA\Property(property: 'message', type: 'string', example: 'Login successful')
                    ]
                )
            ),
            new OA\Response(
                response: 401,
                description: 'Invalid credentials',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: false),
                        new OA\Property(property: 'error', type: 'object', properties: [
                            new OA\Property(property: 'code', type: 'string', example: 'INVALID_CREDENTIALS'),
                            new OA\Property(property: 'message', type: 'string', example: 'Invalid credentials')
                        ])
                    ]
                )
            )
        ]
    )]
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

    #[OA\Get(
        path: '/auth/me',
        summary: 'Get current user',
        description: 'Returns the currently authenticated user information',
        tags: ['Authentication'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'User retrieved successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'data', type: 'object', properties: [
                            new OA\Property(property: 'id', type: 'integer', example: 1),
                            new OA\Property(property: 'name', type: 'string', example: 'Admin User'),
                            new OA\Property(property: 'email', type: 'string', example: 'admin@example.com'),
                            new OA\Property(property: 'role', type: 'string', example: 'admin'),
                            new OA\Property(property: 'orgId', type: 'integer', example: 1)
                        ]),
                        new OA\Property(property: 'message', type: 'string', example: 'User retrieved successfully')
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthorized')
        ]
    )]
    public function me(Request $request): JsonResponse
    {
        $user = $request->attributes->get('auth_user');

        if (!$user) {
            return $this->unauthorizedResponse();
        }

        return $this->successResponse($user, 'User retrieved successfully');
    }

    #[OA\Post(
        path: '/auth/logout',
        summary: 'Logout',
        description: 'Logout the current user (client-side token disposal)',
        tags: ['Authentication'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Logout successful',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'data', type: 'string', nullable: true, example: null),
                        new OA\Property(property: 'message', type: 'string', example: 'Logout successful')
                    ]
                )
            )
        ]
    )]
    public function logout(): JsonResponse
    {
        return $this->successResponse(null, 'Logout successful');
    }

    #[OA\Get(
        path: '/auth/mock-users',
        summary: 'Get mock users',
        description: 'Returns all available mock users for testing. Use these credentials to login.',
        tags: ['Authentication'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Mock users retrieved',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'data', type: 'array', items: new OA\Items(
                            properties: [
                                new OA\Property(property: 'id', type: 'integer', example: 1),
                                new OA\Property(property: 'name', type: 'string', example: 'Admin User'),
                                new OA\Property(property: 'email', type: 'string', example: 'admin@example.com'),
                                new OA\Property(property: 'role', type: 'string', example: 'admin'),
                                new OA\Property(property: 'orgId', type: 'integer', example: 1)
                            ]
                        )),
                        new OA\Property(property: 'message', type: 'string', example: 'Mock users retrieved successfully')
                    ]
                )
            )
        ]
    )]
    public function mockUsers(): JsonResponse
    {
        $users = $this->authService->getAllMockUsers();

        return $this->successResponse($users, 'Mock users retrieved successfully');
    }
}
