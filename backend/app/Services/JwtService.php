<?php

namespace App\Services;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Firebase\JWT\ExpiredException;
use Exception;

class JwtService
{
    private string $secret;
    private int $ttl;
    private string $algorithm = 'HS256';

    public function __construct()
    {
        $this->secret = config('app.jwt_secret', env('JWT_SECRET', 'your-256-bit-secret-key-here'));
        $this->ttl = (int) config('app.jwt_ttl', env('JWT_TTL', 60));
    }

    public function encode(array $payload): string
    {
        $issuedAt = time();
        $expiration = $issuedAt + ($this->ttl * 60);

        $tokenPayload = array_merge($payload, [
            'iat' => $issuedAt,
            'exp' => $expiration,
        ]);

        return JWT::encode($tokenPayload, $this->secret, $this->algorithm);
    }

    public function decode(string $token): ?array
    {
        try {
            $decoded = JWT::decode($token, new Key($this->secret, $this->algorithm));
            return (array) $decoded;
        } catch (ExpiredException $e) {
            return null;
        } catch (Exception $e) {
            return null;
        }
    }

    public function createTokenForUser(array $user): string
    {
        return $this->encode([
            'sub' => $user['id'],
            'email' => $user['email'],
            'role' => $user['role'],
            'orgId' => $user['orgId'],
        ]);
    }

    public function validateToken(string $token): ?array
    {
        return $this->decode($token);
    }

    public function isTokenExpired(string $token): bool
    {
        $decoded = $this->decode($token);
        if (!$decoded) {
            return true;
        }

        return isset($decoded['exp']) && $decoded['exp'] < time();
    }
}
