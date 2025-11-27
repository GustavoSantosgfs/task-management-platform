<?php

namespace Tests\Unit\Services;

use App\Models\Organization;
use App\Models\OrganizationUser;
use App\Models\User;
use App\Services\AuthService;
use App\Services\JwtService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthServiceTest extends TestCase
{
    use RefreshDatabase;

    private AuthService $authService;
    private JwtService $jwtService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->jwtService = new JwtService();
        $this->authService = new AuthService($this->jwtService);
    }

    // Login Tests

    public function test_attempt_login_with_valid_credentials_returns_token_and_user(): void
    {
        Organization::factory()->create(['id' => 1]);

        $result = $this->authService->attemptLogin('admin@example.com', 'password123');

        $this->assertNotNull($result);
        $this->assertArrayHasKey('token', $result);
        $this->assertArrayHasKey('user', $result);
        $this->assertEquals('admin@example.com', $result['user']['email']);
        $this->assertEquals('admin', $result['user']['role']);
    }

    public function test_attempt_login_with_invalid_email_returns_null(): void
    {
        $result = $this->authService->attemptLogin('nonexistent@example.com', 'password123');

        $this->assertNull($result);
    }

    public function test_attempt_login_with_invalid_password_returns_null(): void
    {
        $result = $this->authService->attemptLogin('admin@example.com', 'wrongpassword');

        $this->assertNull($result);
    }

    public function test_attempt_login_syncs_user_to_database(): void
    {
        Organization::factory()->create(['id' => 1]);

        $this->authService->attemptLogin('admin@example.com', 'password123');

        $this->assertDatabaseHas('users', [
            'email' => 'admin@example.com',
        ]);

        $this->assertDatabaseHas('organization_users', [
            'organization_id' => 1,
            'role' => 'admin',
        ]);
    }

    // Get User From Token Tests

    public function test_get_user_from_token_returns_mock_user(): void
    {
        Organization::factory()->create(['id' => 1]);

        $loginResult = $this->authService->attemptLogin('admin@example.com', 'password123');
        $user = $this->authService->getUserFromToken($loginResult['token']);

        $this->assertNotNull($user);
        $this->assertEquals('admin@example.com', $user['email']);
        $this->assertEquals('admin', $user['role']);
        $this->assertEquals(1, $user['orgId']);
    }

    public function test_get_user_from_token_returns_database_user(): void
    {
        $organization = Organization::factory()->create();
        // Use a high ID that doesn't conflict with mock users (1-5)
        $user = User::factory()->create([
            'id' => 100,
            'email' => 'db-user@example.com'
        ]);
        OrganizationUser::factory()->create([
            'organization_id' => $organization->id,
            'user_id' => $user->id,
            'role' => 'member',
        ]);

        $token = $this->jwtService->createTokenForUser([
            'id' => $user->id,
            'email' => $user->email,
            'role' => 'member',
            'orgId' => $organization->id,
        ]);

        $result = $this->authService->getUserFromToken($token);

        $this->assertNotNull($result);
        $this->assertEquals($user->id, $result['id']);
        $this->assertEquals($user->email, $result['email']);
    }

    public function test_get_user_from_token_returns_null_for_invalid_token(): void
    {
        $result = $this->authService->getUserFromToken('invalid.token.here');

        $this->assertNull($result);
    }

    // Get Full User From Token Tests

    public function test_get_full_user_from_token_returns_mock_user_data(): void
    {
        Organization::factory()->create(['id' => 1]);

        $loginResult = $this->authService->attemptLogin('admin@example.com', 'password123');
        $user = $this->authService->getFullUserFromToken($loginResult['token']);

        $this->assertNotNull($user);
        $this->assertArrayHasKey('password', $user);
    }

    public function test_get_full_user_from_token_returns_null_for_invalid_token(): void
    {
        $result = $this->authService->getFullUserFromToken('invalid.token');

        $this->assertNull($result);
    }

    // Get Token Payload Tests

    public function test_get_token_payload_returns_payload(): void
    {
        Organization::factory()->create(['id' => 1]);

        $loginResult = $this->authService->attemptLogin('admin@example.com', 'password123');
        $payload = $this->authService->getTokenPayload($loginResult['token']);

        $this->assertNotNull($payload);
        $this->assertArrayHasKey('sub', $payload);
        $this->assertArrayHasKey('email', $payload);
        $this->assertArrayHasKey('role', $payload);
        $this->assertArrayHasKey('orgId', $payload);
    }

    public function test_get_token_payload_returns_null_for_invalid_token(): void
    {
        $result = $this->authService->getTokenPayload('invalid.token');

        $this->assertNull($result);
    }

    // Get All Mock Users Tests

    public function test_get_all_mock_users_returns_users_without_passwords(): void
    {
        $users = $this->authService->getAllMockUsers();

        $this->assertIsArray($users);
        $this->assertNotEmpty($users);

        foreach ($users as $user) {
            $this->assertArrayHasKey('id', $user);
            $this->assertArrayHasKey('name', $user);
            $this->assertArrayHasKey('email', $user);
            $this->assertArrayHasKey('role', $user);
            $this->assertArrayHasKey('orgId', $user);
            $this->assertArrayNotHasKey('password', $user);
        }
    }

    // Role Tests

    public function test_login_with_different_roles(): void
    {
        Organization::factory()->create(['id' => 1]);

        $roles = [
            'admin@example.com' => 'admin',
            'manager@example.com' => 'project_manager',
            'member@example.com' => 'member',
        ];

        foreach ($roles as $email => $expectedRole) {
            $result = $this->authService->attemptLogin($email, 'password123');

            $this->assertNotNull($result, "Login failed for {$email}");
            $this->assertEquals($expectedRole, $result['user']['role'], "Role mismatch for {$email}");
        }
    }

    // Organization Tests

    public function test_login_with_different_organizations(): void
    {
        Organization::factory()->create(['id' => 1]);
        Organization::factory()->create(['id' => 2]);

        $org1Result = $this->authService->attemptLogin('admin@example.com', 'password123');
        $org2Result = $this->authService->attemptLogin('admin2@example.com', 'password123');

        $this->assertEquals(1, $org1Result['user']['orgId']);
        $this->assertEquals(2, $org2Result['user']['orgId']);
    }
}
