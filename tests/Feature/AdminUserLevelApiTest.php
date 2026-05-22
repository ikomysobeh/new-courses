<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\User;
use App\Models\UserLevel;
use App\Models\UserLevelTier;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminUserLevelApiTest extends TestCase
{
    use RefreshDatabase;

    private function adminToken(): string
    {
        $this->seed(DatabaseSeeder::class);

        return (string) $this->postJson('/api/login', [
            'email'    => 'admin@newproject.test',
            'password' => env('ADMIN_INITIAL_PASSWORD', 'Admin@12345'),
        ])->json('data.token');
    }

    private function createLevelsWithTiers(): array
    {
        $level1 = UserLevel::create([
            'code'            => 'L1',
            'name'            => 'Junior',
            'hierarchy_level' => 1,
        ]);
        $tier1a = UserLevelTier::create(['user_level_id' => $level1->id, 'tier_name' => 'Tier A', 'tier_order' => 1]);
        $tier1b = UserLevelTier::create(['user_level_id' => $level1->id, 'tier_name' => 'Tier B', 'tier_order' => 2]);

        $level2 = UserLevel::create([
            'code'            => 'L2',
            'name'            => 'Senior',
            'hierarchy_level' => 2,
        ]);
        $tier2a = UserLevelTier::create(['user_level_id' => $level2->id, 'tier_name' => 'Tier X', 'tier_order' => 1]);

        return compact('level1', 'level2', 'tier1a', 'tier1b', 'tier2a');
    }

    public function test_admin_can_get_levels_with_tiers(): void
    {
        $token = $this->adminToken();
        // The seeder creates L1–L4 with tiers

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/admin/user-levels/with-tiers');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'code', 'name', 'hierarchy_level', 'tiers'],
                ],
            ]);

        $data = $response->json('data');
        $this->assertNotEmpty($data);

        // Ordered by hierarchy_level ascending
        $levels = array_column($data, 'hierarchy_level');
        $this->assertEquals($levels, collect($levels)->sort()->values()->toArray());

        // Every level has a tiers array
        foreach ($data as $level) {
            $this->assertIsArray($level['tiers']);
        }
    }

    public function test_with_tiers_returns_empty_data_when_no_levels(): void
    {
        $token = $this->adminToken();

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/admin/user-levels/with-tiers');

        $response->assertOk()->assertJson(['data' => []]);
    }

    public function test_unauthenticated_cannot_access_user_levels(): void
    {
        $response = $this->getJson('/api/admin/user-levels/with-tiers');

        $response->assertUnauthorized();
    }

    public function test_user_role_cannot_access_user_levels(): void
    {
        $this->seed(DatabaseSeeder::class);
        $user = User::factory()->create(['role' => 'user']);

        $token = (string) $this->postJson('/api/login', [
            'email'    => $user->email,
            'password' => 'password',
        ])->json('data.token');

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/admin/user-levels/with-tiers');

        $response->assertForbidden();
    }
}
