<?php

namespace Tests\Unit\Services;

use App\Services\JwtService;
use Tests\TestCase;

class JwtServiceTest extends TestCase
{
    private JwtService $jwtService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->jwtService = new JwtService();
    }

    public function test_encode_creates_valid_token(): void
    {
        $payload = [
            'sub' => 1,
            'email' => 'test@example.com',
            'role' => 'admin',
            'orgId' => 1,
        ];

        $token = $this->jwtService->encode($payload);

        $this->assertIsString($token);
        $this->assertNotEmpty($token);
        // JWT tokens have 3 parts separated by dots
        $this->assertCount(3, explode('.', $token));
    }

    public function test_decode_returns_payload_for_valid_token(): void
    {
        $payload = [
            'sub' => 1,
            'email' => 'test@example.com',
            'role' => 'admin',
            'orgId' => 1,
        ];

        $token = $this->jwtService->encode($payload);
        $decoded = $this->jwtService->decode($token);

        $this->assertIsArray($decoded);
        $this->assertEquals($payload['sub'], $decoded['sub']);
        $this->assertEquals($payload['email'], $decoded['email']);
        $this->assertEquals($payload['role'], $decoded['role']);
        $this->assertEquals($payload['orgId'], $decoded['orgId']);
    }

    public function test_decode_returns_null_for_invalid_token(): void
    {
        $invalidToken = 'invalid.token.here';

        $decoded = $this->jwtService->decode($invalidToken);

        $this->assertNull($decoded);
    }

    public function test_decode_returns_null_for_malformed_token(): void
    {
        $malformedToken = 'not-a-jwt';

        $decoded = $this->jwtService->decode($malformedToken);

        $this->assertNull($decoded);
    }

    public function test_create_token_for_user_includes_required_claims(): void
    {
        $user = [
            'id' => 1,
            'email' => 'admin@example.com',
            'role' => 'admin',
            'orgId' => 1,
        ];

        $token = $this->jwtService->createTokenForUser($user);
        $decoded = $this->jwtService->decode($token);

        $this->assertIsArray($decoded);
        $this->assertEquals($user['id'], $decoded['sub']);
        $this->assertEquals($user['email'], $decoded['email']);
        $this->assertEquals($user['role'], $decoded['role']);
        $this->assertEquals($user['orgId'], $decoded['orgId']);
        $this->assertArrayHasKey('iat', $decoded);
        $this->assertArrayHasKey('exp', $decoded);
    }

    public function test_validate_token_returns_payload_for_valid_token(): void
    {
        $payload = [
            'sub' => 1,
            'email' => 'test@example.com',
        ];

        $token = $this->jwtService->encode($payload);
        $validated = $this->jwtService->validateToken($token);

        $this->assertIsArray($validated);
        $this->assertEquals($payload['sub'], $validated['sub']);
    }

    public function test_validate_token_returns_null_for_invalid_token(): void
    {
        $validated = $this->jwtService->validateToken('invalid.token');

        $this->assertNull($validated);
    }

    public function test_is_token_expired_returns_false_for_valid_token(): void
    {
        $payload = ['sub' => 1];
        $token = $this->jwtService->encode($payload);

        $isExpired = $this->jwtService->isTokenExpired($token);

        $this->assertFalse($isExpired);
    }

    public function test_is_token_expired_returns_true_for_invalid_token(): void
    {
        $isExpired = $this->jwtService->isTokenExpired('invalid.token');

        $this->assertTrue($isExpired);
    }

    public function test_token_contains_expiration_time(): void
    {
        $payload = ['sub' => 1];
        $token = $this->jwtService->encode($payload);
        $decoded = $this->jwtService->decode($token);

        $this->assertArrayHasKey('exp', $decoded);
        $this->assertGreaterThan(time(), $decoded['exp']);
    }

    public function test_token_contains_issued_at_time(): void
    {
        $payload = ['sub' => 1];
        $token = $this->jwtService->encode($payload);
        $decoded = $this->jwtService->decode($token);

        $this->assertArrayHasKey('iat', $decoded);
        $this->assertLessThanOrEqual(time(), $decoded['iat']);
    }
}
