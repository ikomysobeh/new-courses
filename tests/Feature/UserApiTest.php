<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\User;
use App\Models\UserLevelTier;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserApiTest extends TestCase
{
    use RefreshDatabase;

    private function adminToken(): string
    {
        $this->seed(DatabaseSeeder::class);

        $response = $this->postJson('/api/login', [
            'email'    => 'admin@newproject.test',
            'password' => env('ADMIN_INITIAL_PASSWORD', 'Admin@12345'),
        ]);

        return $response->json('data.token');
    }

    private function authHeader(string $token): array
    {
        return ['Authorization' => 'Bearer ' . $token];
    }

    public function test_get_all_users_returns_paginated_list(): void
    {
        $token = $this->adminToken();

        $response = $this->withHeaders($this->authHeader($token))
            ->getJson('/api/admin/users/getAll');

        $response
            ->assertOk()
            ->assertJsonStructure([
                'data',
                'meta' => ['current_page', 'total', 'per_page'],
                'cards' => [
                    '*' => ['key', 'title', 'value'],
                ],
            ]);

        $response->assertJsonPath('cards.0.key', 'total_users');
        $response->assertJsonPath('cards.1.key', 'admin_users');
        $response->assertJsonPath('cards.2.key', 'regular_users');
        $response->assertJsonPath('cards.3.key', 'users_with_manager');
    }

    public function test_get_all_requires_authentication(): void
    {
        $this->getJson('/api/admin/users/getAll')->assertStatus(401);
    }

    public function test_admin_can_create_user(): void
    {
        $token = $this->adminToken();

        $response = $this->withHeaders($this->authHeader($token))
            ->postJson('/api/admin/users/create', [
                'name'              => 'John Doe',
                'email'             => 'john.doe@example.com',
                'password'          => 'Secret@12345',
                'password_confirmation' => 'Secret@12345',
            ]);

        $response
            ->assertStatus(201)
            ->assertJsonPath('data.email', 'john.doe@example.com')
            ->assertJsonPath('data.role', 'user');

        $this->assertDatabaseHas('users', ['email' => 'john.doe@example.com', 'role' => 'user']);
    }

    public function test_create_user_with_admin_role(): void
    {
        $token = $this->adminToken();

        $response = $this->withHeaders($this->authHeader($token))
            ->postJson('/api/admin/users/create', [
                'name'              => 'Super Admin',
                'email'             => 'super@example.com',
                'password'          => 'Secret@12345',
                'password_confirmation' => 'Secret@12345',
                'role'              => 'admin',
            ]);

        $response
            ->assertStatus(201)
            ->assertJsonPath('data.role', 'admin');

        $this->assertDatabaseHas('users', ['email' => 'super@example.com', 'role' => 'admin']);
    }

    public function test_create_user_fails_with_duplicate_email(): void
    {
        $token = $this->adminToken();

        $this->withHeaders($this->authHeader($token))
            ->postJson('/api/admin/users/create', [
                'name'              => 'Test User',
                'email'             => 'admin@newproject.test',
                'password'          => 'Secret@12345',
                'password_confirmation' => 'Secret@12345',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_admin_can_update_user(): void
    {
        $token = $this->adminToken();

        $user = User::factory()->create([
            'name'  => 'Old Name',
            'email' => 'old@example.com',
            'role'  => 'user',
        ]);

        $response = $this->withHeaders($this->authHeader($token))
            ->putJson("/api/admin/users/update/{$user->id}", [
                'name' => 'New Name',
            ]);

        $response
            ->assertOk()
            ->assertJsonPath('data.name', 'New Name');

        $this->assertDatabaseHas('users', ['id' => $user->id, 'name' => 'New Name']);
    }

    public function test_admin_can_change_user_role_to_admin(): void
    {
        $token = $this->adminToken();

        $user = User::factory()->create(['role' => 'user']);

        $response = $this->withHeaders($this->authHeader($token))
            ->putJson("/api/admin/users/update/{$user->id}", [
                'role' => 'admin',
            ]);

        $response
            ->assertOk()
            ->assertJsonPath('data.role', 'admin');

        $this->assertDatabaseHas('users', ['id' => $user->id, 'role' => 'admin']);
    }

    public function test_admin_can_delete_user(): void
    {
        $token = $this->adminToken();

        $user = User::factory()->create();

        $response = $this->withHeaders($this->authHeader($token))
            ->deleteJson("/api/admin/users/delete/{$user->id}");

        $response
            ->assertOk()
            ->assertJsonPath('message', 'User deleted successfully.');

        $this->assertSoftDeleted('users', ['id' => $user->id]);
    }

    public function test_delete_fails_if_user_is_manager(): void
    {
        $token = $this->adminToken();

        $manager = User::factory()->create();
        User::factory()->create(['report_to' => $manager->id]);

        $response = $this->withHeaders($this->authHeader($token))
            ->deleteJson("/api/admin/users/delete/{$manager->id}");

        $response->assertStatus(422);
    }

    public function test_update_returns_404_for_nonexistent_user(): void
    {
        $token = $this->adminToken();

        $this->withHeaders($this->authHeader($token))
            ->putJson('/api/admin/users/update/99999', ['name' => 'X'])
            ->assertStatus(404);
    }

    public function test_get_all_filters_by_search(): void
    {
        $token = $this->adminToken();

        User::factory()->create(['name' => 'Unique Search Name', 'email' => 'unique@example.com']);

        $response = $this->withHeaders($this->authHeader($token))
            ->getJson('/api/admin/users/getAll?search=Unique+Search+Name');

        $response
            ->assertOk()
            ->assertJsonPath('data.0.name', 'Unique Search Name');
    }
}
