<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UnifiedLoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_unified_login_works_for_admin(): void
    {
        $this->seed(DatabaseSeeder::class);

        $response = $this->postJson('/api/login', [
            'email' => 'admin@newproject.test',
            'password' => env('ADMIN_INITIAL_PASSWORD', 'Admin@12345'),
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('data.user.email', 'admin@newproject.test')
            ->assertJsonPath('data.user.role', 'admin')
            ->assertJsonStructure([
                'data' => [
                    'user' => ['id', 'name', 'email', 'role'],
                    'token',
                    'token_type',
                ],
            ]);
    }

    public function test_unified_login_works_for_user(): void
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
            ->assertJsonPath('data.user.role', 'user');
    }
}
