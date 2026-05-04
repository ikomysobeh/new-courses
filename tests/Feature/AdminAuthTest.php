<?php

namespace Tests\Feature;

use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_login_and_receive_token(): void
    {
        $this->seed(DatabaseSeeder::class);

        $response = $this->postJson('/api/login', [
            'email' => 'admin@newproject.test',
            'password' => env('ADMIN_INITIAL_PASSWORD', 'Admin@12345'),
        ]);

        $response
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'user' => ['id', 'name', 'email', 'role'],
                    'token',
                    'token_type',
                ],
            ]);
    }

    public function test_login_rejects_invalid_credentials(): void
    {
        $this->seed(DatabaseSeeder::class);

        $response = $this->postJson('/api/login', [
            'email' => 'admin@newproject.test',
            'password' => 'wrong-password',
        ]);

        $response->assertStatus(422);
    }

    public function test_me_requires_authentication(): void
    {
        $response = $this->getJson('/api/admin/me');

        $response->assertStatus(401);
    }

    public function test_admin_can_access_me_with_valid_token(): void
    {
        $this->seed(DatabaseSeeder::class);

        $login = $this->postJson('/api/login', [
            'email' => 'admin@newproject.test',
            'password' => env('ADMIN_INITIAL_PASSWORD', 'Admin@12345'),
        ]);

        $token = $login->json('data.token');

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/admin/me');

        $response
            ->assertOk()
            ->assertJsonPath('data.email', 'admin@newproject.test')
            ->assertJsonPath('data.role', 'admin');
    }

    public function test_admin_can_logout_and_token_is_revoked(): void
    {
        $this->seed(DatabaseSeeder::class);

        $login = $this->postJson('/api/login', [
            'email' => 'admin@newproject.test',
            'password' => env('ADMIN_INITIAL_PASSWORD', 'Admin@12345'),
        ]);

        $token = $login->json('data.token');

        $logout = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/logout');

        $logout->assertOk();

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }
}
