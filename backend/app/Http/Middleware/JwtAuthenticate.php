<?php

namespace App\Http\Middleware;

use App\Services\AuthService;
use App\Traits\ApiResponse;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class JwtAuthenticate
{
    use ApiResponse;

    public function __construct(
        private AuthService $authService
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $token = $this->extractToken($request);

        if (!$token) {
            return $this->unauthorizedResponse('No token provided');
        }

        $user = $this->authService->getUserFromToken($token);

        if (!$user) {
            return $this->unauthorizedResponse('Invalid or expired token');
        }

        $payload = $this->authService->getTokenPayload($token);

        $request->attributes->set('auth_user', $user);
        $request->attributes->set('auth_token_payload', $payload);
        $request->attributes->set('auth_user_id', $user['id']);
        $request->attributes->set('auth_org_id', $user['orgId']);
        $request->attributes->set('auth_role', $user['role']);

        return $next($request);
    }

    private function extractToken(Request $request): ?string
    {
        $header = $request->header('Authorization');

        if (!$header) {
            return null;
        }

        if (preg_match('/Bearer\s+(.*)$/i', $header, $matches)) {
            return $matches[1];
        }

        return null;
    }
}
