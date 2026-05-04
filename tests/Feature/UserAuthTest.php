<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_login_and_receive_token(): void
    {
        $this->seed(DatabaseSeeder::class);

        $user = User::factory()->create([
            'role' => 'user',
            'password' => Hash::make('User@12345'),
        ]);

        $response = $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'User@12345',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('data.user.id', $user->id)
            ->assertJsonPath('data.user.role', 'user')
            ->assertJsonStructure([
                'data' => [
                    'user' => ['id', 'name', 'email', 'role'],
                    'token',
                    'token_type',
                ],
            ]);
    }

    public function test_unified_login_allows_admin_credentials(): void
    {
        $this->seed(DatabaseSeeder::class);

        $response = $this->postJson('/api/login', [
            'email' => 'admin@newproject.test',
            'password' => env('ADMIN_INITIAL_PASSWORD', 'Admin@12345'),
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('data.user.role', 'admin');
    }

    public function test_user_me_requires_authentication(): void
    {
        $response = $this->getJson('/api/user/me');

        $response->assertStatus(401);
    }

    public function test_user_can_access_me_with_valid_token(): void
    {
        $this->seed(DatabaseSeeder::class);

        $user = User::factory()->create([
            'role' => 'user',
            'password' => Hash::make('User@12345'),
        ]);

        $login = $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'User@12345',
        ]);

        $token = $login->json('data.token');

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/user/me');

        $response
            ->assertOk()
            ->assertJsonPath('data.email', $user->email)
            ->assertJsonPath('data.role', 'user');
    }

    public function test_user_can_logout_and_token_is_revoked(): void
    {
        $this->seed(DatabaseSeeder::class);

        $user = User::factory()->create([
            'role' => 'user',
            'password' => Hash::make('User@12345'),
        ]);

        $login = $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'User@12345',
        ]);

        $token = $login->json('data.token');

        $logout = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/logout');

        $logout->assertOk();

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }
}
