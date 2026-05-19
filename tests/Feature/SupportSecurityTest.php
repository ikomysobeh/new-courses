<?php

namespace Tests\Feature;

use App\Models\BugReport;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Security tests: access control for feedback, bug reports, and activity logs.
 */
class SupportSecurityTest extends TestCase
{
    use RefreshDatabase;

    private function userToken(): string
    {
        $this->seed(DatabaseSeeder::class);
        $user = User::factory()->create(['role' => 'user']);

        return (string) $this->postJson('/api/login', [
            'email'    => $user->email,
            'password' => 'password',
        ])->json('data.token');
    }

    // ---- Unauthenticated → 401 ----

    public function test_unauthenticated_cannot_access_admin_feedback(): void
    {
        $this->getJson('/api/admin/feedback/getAll')->assertUnauthorized();
    }

    public function test_unauthenticated_cannot_access_admin_bug_reports(): void
    {
        $this->getJson('/api/admin/bug-reports/getAll')->assertUnauthorized();
    }

    public function test_unauthenticated_cannot_access_admin_activity_logs(): void
    {
        $this->getJson('/api/admin/activity-logs/getAll')->assertUnauthorized();
    }

    public function test_unauthenticated_cannot_access_user_feedback(): void
    {
        $this->getJson('/api/user/feedback/getAll')->assertUnauthorized();
    }

    // ---- Non-admin → 403 on admin endpoints ----

    public function test_non_admin_cannot_list_admin_feedback(): void
    {
        $token = $this->userToken();

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/admin/feedback/getAll')
            ->assertForbidden();
    }

    public function test_non_admin_cannot_respond_to_feedback(): void
    {
        $token = $this->userToken();

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->putJson('/api/admin/feedback/respond/1', [
                'admin_response' => 'Hacking',
                'status'         => 'approved',
            ])
            ->assertForbidden();
    }

    public function test_non_admin_cannot_create_bug_report(): void
    {
        $token = $this->userToken();

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/admin/bug-reports/create', [
                'title'       => 'Injection',
                'description' => 'Trying',
                'priority'    => 'low',
            ])
            ->assertForbidden();
    }

    public function test_non_admin_cannot_list_bug_reports(): void
    {
        $token = $this->userToken();

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/admin/bug-reports/getAll')
            ->assertForbidden();
    }

    public function test_non_admin_cannot_assign_bug_report(): void
    {
        $token = $this->userToken();

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->putJson('/api/admin/bug-reports/assign/1', ['assigned_to' => 1])
            ->assertForbidden();
    }

    public function test_non_admin_cannot_resolve_bug_report(): void
    {
        $token = $this->userToken();

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->putJson('/api/admin/bug-reports/resolve/1')
            ->assertForbidden();
    }

    public function test_non_admin_cannot_access_activity_logs(): void
    {
        $token = $this->userToken();

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/admin/activity-logs/getAll')
            ->assertForbidden();
    }

    // ---- No user bug report endpoints exist ----

    public function test_no_user_bug_report_create_endpoint_exists(): void
    {
        $token = $this->userToken();

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/user/bug-reports/create', [
                'title'       => 'Should 404',
                'description' => 'Not allowed',
                'priority'    => 'low',
            ])
            ->assertNotFound();
    }

    public function test_no_user_bug_report_list_endpoint_exists(): void
    {
        $token = $this->userToken();

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/user/bug-reports/getAll')
            ->assertNotFound();
    }
}
