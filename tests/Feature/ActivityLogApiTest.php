<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\BugReport;
use App\Models\EmployeeFeedback;
use App\Models\User;
use App\Services\ActivityService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ActivityLogApiTest extends TestCase
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

    private function userToken(User $user): string
    {
        return (string) $this->postJson('/api/login', [
            'email'    => $user->email,
            'password' => 'password',
        ])->json('data.token');
    }

    // ---- getAll ----

    public function test_admin_can_list_activity_logs(): void
    {
        $token = $this->adminToken();
        $admin = User::where('role', 'admin')->first();

        ActivityService::log('Test action', ActivityService::ACTION_FEEDBACK_SUBMITTED, $admin);
        ActivityService::log('Another action', ActivityService::ACTION_BUG_REPORT_SUBMITTED);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/admin/activity-logs/getAll');

        $response->assertOk()
            ->assertJsonStructure(['data' => [['id', 'action', 'description', 'created_at']]]);
    }

    public function test_admin_can_filter_logs_by_user_id(): void
    {
        $token = $this->adminToken();
        $admin = User::where('role', 'admin')->first();
        $user  = User::factory()->create();

        ActivityService::log('Admin log', ActivityService::ACTION_FEEDBACK_SUBMITTED, $admin);
        ActivityService::log('User log',  ActivityService::ACTION_FEEDBACK_SUBMITTED, $user);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/admin/activity-logs/getAll?user_id=' . $user->id);

        $response->assertOk();
        $data = $response->json('data');
        $this->assertCount(1, $data);
    }

    public function test_admin_can_filter_logs_by_action(): void
    {
        $token = $this->adminToken();

        ActivityService::log('Feedback', ActivityService::ACTION_FEEDBACK_SUBMITTED);
        ActivityService::log('Bug',      ActivityService::ACTION_BUG_REPORT_SUBMITTED);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/admin/activity-logs/getAll?action=feedback.submitted');

        $response->assertOk();
        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertEquals('feedback.submitted', $data[0]['action']);
    }

    public function test_admin_can_filter_logs_by_model_type(): void
    {
        $token = $this->adminToken();
        $admin = User::where('role', 'admin')->first();

        $feedback = EmployeeFeedback::create([
            'user_id' => $admin->id, 'type' => 'general',
            'title' => 'T', 'description' => 'D', 'status' => 'pending',
        ]);

        ActivityService::log('Feedback log', ActivityService::ACTION_FEEDBACK_SUBMITTED, null, $feedback);
        ActivityService::log('Bug log',      ActivityService::ACTION_BUG_REPORT_SUBMITTED);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/admin/activity-logs/getAll?model_type=' . urlencode(EmployeeFeedback::class));

        $response->assertOk();
        $data = $response->json('data');
        $this->assertCount(1, $data);
    }

    public function test_admin_can_filter_logs_by_date_from(): void
    {
        $token = $this->adminToken();

        $old = ActivityLog::create(['description' => 'Old log', 'action' => 'test']);
        // created_at is not in $fillable — update via query builder to bypass mass-assignment
        ActivityLog::where('id', $old->id)->update(['created_at' => now()->subDays(10)]);

        ActivityService::log('New log', 'test.action');

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/admin/activity-logs/getAll?date_from=' . now()->subDay()->toDateString());

        $response->assertOk();
        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertEquals('New log', $data[0]['description']);
    }

    // ---- user/{userId} ----

    public function test_admin_can_get_activity_logs_for_specific_user(): void
    {
        $token = $this->adminToken();
        $admin = User::where('role', 'admin')->first();
        $user  = User::factory()->create();

        ActivityService::log('Admin did something', ActivityService::ACTION_FEEDBACK_SUBMITTED, $admin);
        ActivityService::log('User did something',  ActivityService::ACTION_FEEDBACK_SUBMITTED, $user);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/admin/activity-logs/user/' . $user->id);

        $response->assertOk();
        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertEquals('User did something', $data[0]['description']);
    }

    // ---- Security ----

    public function test_unauthenticated_cannot_access_activity_logs(): void
    {
        $this->getJson('/api/admin/activity-logs/getAll')->assertUnauthorized();
    }

    public function test_non_admin_cannot_access_activity_logs(): void
    {
        $this->seed(DatabaseSeeder::class);
        $user  = User::factory()->create(['role' => 'user']);
        $token = $this->userToken($user);

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/admin/activity-logs/getAll')
            ->assertForbidden();
    }
}
