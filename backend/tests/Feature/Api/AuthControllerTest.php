<?php

namespace Tests\Feature\Api;

use App\Models\Organization;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Organization::factory()->create(['id' => 1]);
        Organization::factory()->create(['id' => 2]);
    }

    // Login Tests
    public function test_login_with_valid_credentials_returns_token(): void
    {
        $response = $this->postJson('/api/auth/login', [
            'email' => 'admin@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'token',
                    'user' => [
                        'id',
                        'name',
                        'email',
                        'role',
                        'orgId',
                    ],
                ],
                'message',
            ])
            ->assertJson([
                'success' => true,
                'message' => 'Login successful',
            ]);
    }

    public function test_login_with_invalid_email_returns_401(): void
    {
        $response = $this->postJson('/api/auth/login', [
            'email' => 'nonexistent@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(401)
            ->assertJson([
                'success' => false,
                'error' => [
                    'code' => 'INVALID_CREDENTIALS',
                    'message' => 'Invalid credentials',
                ],
            ]);
    }

    public function test_login_with_invalid_password_returns_401(): void
    {
        $response = $this->postJson('/api/auth/login', [
            'email' => 'admin@example.com',
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(401)
            ->assertJson([
                'success' => false,
                'error' => [
                    'code' => 'INVALID_CREDENTIALS',
                ],
            ]);
    }

    public function test_login_without_email_returns_validation_error(): void
    {
        $response = $this->postJson('/api/auth/login', [
            'password' => 'password123',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_login_without_password_returns_validation_error(): void
    {
        $response = $this->postJson('/api/auth/login', [
            'email' => 'admin@example.com',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    }

    public function test_login_with_invalid_email_format_returns_validation_error(): void
    {
        $response = $this->postJson('/api/auth/login', [
            'email' => 'not-an-email',
            'password' => 'password123',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    // Me Tests
    public function test_me_returns_authenticated_user(): void
    {
        $token = $this->getAuthToken('admin@example.com');

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/auth/me');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'email' => 'admin@example.com',
                    'role' => 'admin',
                ],
            ]);
    }

    public function test_me_without_token_returns_401(): void
    {
        $response = $this->getJson('/api/auth/me');

        $response->assertStatus(401);
    }

    public function test_me_with_invalid_token_returns_401(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer invalid-token')
            ->getJson('/api/auth/me');

        $response->assertStatus(401);
    }

    // Logout Tests
    public function test_logout_returns_success(): void
    {
        $token = $this->getAuthToken('admin@example.com');

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/auth/logout');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Logout successful',
            ]);
    }

    public function test_logout_without_token_returns_401(): void
    {
        $response = $this->postJson('/api/auth/logout');

        $response->assertStatus(401);
    }

    // Mock Users Tests
    public function test_mock_users_returns_list_of_users(): void
    {
        $response = $this->getJson('/api/auth/mock-users');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'email',
                        'role',
                        'orgId',
                    ],
                ],
            ]);
    }

    public function test_mock_users_does_not_expose_passwords(): void
    {
        $response = $this->getJson('/api/auth/mock-users');

        $response->assertStatus(200);
        $data = $response->json('data');

        foreach ($data as $user) {
            $this->assertArrayNotHasKey('password', $user);
        }
    }

    // Role-based login tests
    public function test_admin_login_returns_admin_role(): void
    {
        $response = $this->postJson('/api/auth/login', [
            'email' => 'admin@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'user' => [
                        'role' => 'admin',
                        'orgId' => 1,
                    ],
                ],
            ]);
    }

    public function test_project_manager_login_returns_project_manager_role(): void
    {
        $response = $this->postJson('/api/auth/login', [
            'email' => 'manager@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'user' => [
                        'role' => 'project_manager',
                    ],
                ],
            ]);
    }

    public function test_member_login_returns_member_role(): void
    {
        $response = $this->postJson('/api/auth/login', [
            'email' => 'member@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'user' => [
                        'role' => 'member',
                    ],
                ],
            ]);
    }

    public function test_different_organization_user_login(): void
    {
        $response = $this->postJson('/api/auth/login', [
            'email' => 'admin2@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'user' => [
                        'orgId' => 2,
                    ],
                ],
            ]);
    }

    // Helper method
    private function getAuthToken(string $email): string
    {
        $response = $this->postJson('/api/auth/login', [
            'email' => $email,
            'password' => 'password123',
        ]);

        return $response->json('data.token');
    }
}
