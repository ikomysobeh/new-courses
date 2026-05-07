<?php

namespace Tests\Feature;

use App\Models\Clocking;
use App\Models\Course;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAttendanceApiTest extends TestCase
{
    use RefreshDatabase;

    private function adminToken(): string
    {
        $this->seed(DatabaseSeeder::class);

        return (string) $this->postJson('/api/login', [
            'email' => 'admin@newproject.test',
            'password' => env('ADMIN_INITIAL_PASSWORD', 'Admin@12345'),
        ])->json('data.token');
    }

    private function createClocking(User $user): Clocking
    {
        return Clocking::query()->create([
            'user_id'  => $user->id,
            'clock_in' => now()->subHour(),
        ]);
    }

    public function test_admin_can_get_all_clocking_records(): void
    {
        $token = $this->adminToken();
        $user  = User::factory()->create(['role' => 'user']);
        $this->createClocking($user);
        $this->createClocking($user);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/admin/attendance/getAll');

        $response->assertOk()->assertJsonStructure([
            'data',
            'meta',
            'cards' => [
                '*' => ['key', 'title', 'value'],
            ],
        ]);
        $response->assertJsonPath('cards.0.key', 'total_clocking_records');
        $this->assertGreaterThanOrEqual(2, count($response->json('data')));
    }

    public function test_admin_can_update_clocking_record(): void
    {
        $token    = $this->adminToken();
        $user     = User::factory()->create(['role' => 'user']);
        $clocking = $this->createClocking($user);

        $clockIn  = now()->subHours(2)->toDateTimeString();
        $clockOut = now()->subHour()->toDateTimeString();

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->putJson('/api/admin/attendance/update/' . $clocking->id, [
                'clock_in'  => $clockIn,
                'clock_out' => $clockOut,
                'rating'    => 4,
            ]);

        $response->assertOk()
            ->assertJsonPath('data.rating', 4);

        $updated = Clocking::query()->find($clocking->id);
        $this->assertNotNull($updated->duration_in_minutes);
        $this->assertGreaterThan(0, $updated->duration_in_minutes);
    }

    public function test_admin_can_delete_clocking_record(): void
    {
        $token    = $this->adminToken();
        $user     = User::factory()->create(['role' => 'user']);
        $clocking = $this->createClocking($user);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->deleteJson('/api/admin/attendance/delete/' . $clocking->id);

        $response->assertOk();
        $this->assertDatabaseMissing('clockings', ['id' => $clocking->id]);
    }
}
