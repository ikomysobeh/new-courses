<?php

namespace Tests\Feature;

use App\Models\Clocking;
use App\Models\Course;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserClockingApiTest extends TestCase
{
    use RefreshDatabase;

    private function userToken(User $user): string
    {
        return (string) $this->postJson('/api/login', [
            'email'    => $user->email,
            'password' => 'password',
        ])->json('data.token');
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_user_can_clock_in_without_course(): void
    {
        $user  = User::factory()->create(['role' => 'user']);
        $token = $this->userToken($user);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/user/clocking/clockIn');

        $response->assertCreated()
            ->assertJsonPath('data.user_id', $user->id);

        $this->assertDatabaseHas('clockings', ['user_id' => $user->id, 'course_id' => null]);
    }

    public function test_user_can_clock_in_with_course_id(): void
    {
        $admin  = User::query()->where('role', 'admin')->first();
        $user   = User::factory()->create(['role' => 'user']);
        $token  = $this->userToken($user);
        $course = Course::query()->create([
            'name'       => 'Clock Test',
            'status'     => 'published',
            'privacy'    => 'public',
            'created_by' => $admin->id,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/user/clocking/clockIn', ['course_id' => $course->id]);

        $response->assertCreated()->assertJsonPath('data.course_id', $course->id);
    }

    public function test_user_cannot_clock_in_if_already_has_open_session(): void
    {
        $user  = User::factory()->create(['role' => 'user']);
        $token = $this->userToken($user);

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/user/clocking/clockIn')->assertCreated();

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/user/clocking/clockIn')->assertUnprocessable();

        $this->assertDatabaseCount('clockings', 1);
    }

    public function test_user_can_clock_out_and_duration_is_calculated(): void
    {
        $user  = User::factory()->create(['role' => 'user']);
        $token = $this->userToken($user);

        Clocking::query()->create([
            'user_id'  => $user->id,
            'clock_in' => now()->subHours(2),
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/user/clocking/clockOut', ['rating' => 4, 'comment' => 'Great session']);

        $response->assertOk();

        $clocking = Clocking::query()->where('user_id', $user->id)->first();
        $this->assertNotNull($clocking->clock_out);
        $this->assertNotNull($clocking->duration_in_minutes);
        $this->assertGreaterThan(0, $clocking->duration_in_minutes);
        $this->assertSame(4, $clocking->rating);
    }

    public function test_user_cannot_clock_out_without_open_session(): void
    {
        $user  = User::factory()->create(['role' => 'user']);
        $token = $this->userToken($user);

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/user/clocking/clockOut')
            ->assertNotFound();
    }

    public function test_user_can_view_clocking_history(): void
    {
        $user  = User::factory()->create(['role' => 'user']);
        $token = $this->userToken($user);

        Clocking::query()->create([
            'user_id'              => $user->id,
            'clock_in'             => now()->subHours(3),
            'clock_out'            => now()->subHours(2),
            'duration_in_minutes'  => 60,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/user/clocking/history');

        $response->assertOk()->assertJsonStructure(['data', 'meta']);
        $this->assertGreaterThanOrEqual(1, count($response->json('data')));
    }

    public function test_user_can_check_active_session(): void
    {
        $user  = User::factory()->create(['role' => 'user']);
        $token = $this->userToken($user);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/user/clocking/active');

        $response->assertOk()->assertJsonPath('data', null);

        Clocking::query()->create([
            'user_id'  => $user->id,
            'clock_in' => now()->subHour(),
        ]);

        $response2 = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/user/clocking/active');

        $response2->assertOk();
        $this->assertSame($user->id, $response2->json('data.user_id'));
    }
}
