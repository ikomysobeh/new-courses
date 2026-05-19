<?php

namespace Tests\Feature;

use App\Models\BugReport;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminBugReportApiTest extends TestCase
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

    private function adminUser(): User
    {
        return User::where('role', 'admin')->first();
    }

    private function createReport(array $overrides = []): BugReport
    {
        $admin = User::where('role', 'admin')->firstOrFail();

        return BugReport::create(array_merge([
            'reported_by' => $admin->id,
            'priority'    => 'medium',
            'status'      => 'open',
            'title'       => 'Test Bug',
            'description' => 'Something is broken',
        ], $overrides));
    }

    // ---- create ----

    public function test_admin_can_create_bug_report(): void
    {
        $token = $this->adminToken();

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/admin/bug-reports/create', [
                'title'               => 'Login fails',
                'description'         => 'Cannot log in with correct credentials.',
                'priority'            => 'high',
                'steps_to_reproduce'  => '1. Go to login 2. Enter creds 3. Click submit',
                'page_url'            => 'https://app.test/login',
            ]);

        $response->assertCreated()
            ->assertJsonPath('data.title', 'Login fails')
            ->assertJsonPath('data.priority', 'high')
            ->assertJsonPath('data.status', 'open');

        $this->assertDatabaseHas('bug_reports', [
            'title'       => 'Login fails',
            'reported_by' => $this->adminUser()->id,
        ]);
    }

    public function test_create_requires_title_description_priority(): void
    {
        $token = $this->adminToken();

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/admin/bug-reports/create', [])
            ->assertUnprocessable();
    }

    public function test_create_rejects_invalid_priority(): void
    {
        $token = $this->adminToken();

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/admin/bug-reports/create', [
                'title'       => 'Bug',
                'description' => 'Desc',
                'priority'    => 'extreme', // not in enum
            ])
            ->assertUnprocessable();
    }

    public function test_reported_by_is_set_from_auth_not_request_body(): void
    {
        $token = $this->adminToken();
        $admin = $this->adminUser();

        $otherUser = User::factory()->create(['role' => 'admin']);

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/admin/bug-reports/create', [
                'title'       => 'Auth check',
                'description' => 'Desc',
                'priority'    => 'low',
                'reported_by' => $otherUser->id, // must be ignored
            ]);

        $this->assertDatabaseHas('bug_reports', [
            'title'       => 'Auth check',
            'reported_by' => $admin->id,
        ]);
    }

    // ---- getAll ----

    public function test_admin_can_list_all_bug_reports(): void
    {
        $token = $this->adminToken();
        $this->createReport();
        $this->createReport(['priority' => 'critical', 'status' => 'in_progress']);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/admin/bug-reports/getAll');

        $response->assertOk()
            ->assertJsonStructure(['data' => [['id', 'title', 'priority', 'status', 'reported_by']]]);

        $this->assertCount(2, $response->json('data'));
    }

    public function test_admin_can_filter_bug_reports_by_status(): void
    {
        $token = $this->adminToken();
        $this->createReport(['status' => 'open']);
        $this->createReport(['status' => 'resolved']);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/admin/bug-reports/getAll?status=open');

        $response->assertOk();
        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertEquals('open', $data[0]['status']);
    }

    public function test_admin_can_filter_bug_reports_by_priority(): void
    {
        $token = $this->adminToken();
        $this->createReport(['priority' => 'low']);
        $this->createReport(['priority' => 'critical']);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/admin/bug-reports/getAll?priority=critical');

        $response->assertOk();
        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertEquals('critical', $data[0]['priority']);
    }

    public function test_admin_can_search_bug_reports(): void
    {
        $token = $this->adminToken();
        $this->createReport(['title' => 'Login broken']);
        $this->createReport(['title' => 'Dashboard layout']);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/admin/bug-reports/getAll?search=Login');

        $response->assertOk();
        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertStringContainsString('Login', $data[0]['title']);
    }

    // ---- getById ----

    public function test_admin_can_view_single_bug_report(): void
    {
        $token  = $this->adminToken();
        $report = $this->createReport();

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/admin/bug-reports/getById/' . $report->id);

        $response->assertOk()
            ->assertJsonPath('data.id', $report->id)
            ->assertJsonStructure(['data' => ['id', 'title', 'priority', 'status', 'reported_by', 'assigned_to']]);
    }

    // ---- update ----

    public function test_admin_can_update_priority_and_status(): void
    {
        $token  = $this->adminToken();
        $report = $this->createReport(['priority' => 'low', 'status' => 'open']);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->putJson('/api/admin/bug-reports/update/' . $report->id, [
                'priority' => 'critical',
                'status'   => 'in_progress',
            ]);

        $response->assertOk()
            ->assertJsonPath('data.priority', 'critical')
            ->assertJsonPath('data.status', 'in_progress');
    }

    // ---- assign ----

    public function test_admin_can_assign_bug_report_to_admin_user(): void
    {
        $token       = $this->adminToken();
        $report      = $this->createReport();
        $adminUser   = User::factory()->create(['role' => 'admin']);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->putJson('/api/admin/bug-reports/assign/' . $report->id, [
                'assigned_to' => $adminUser->id,
            ]);

        $response->assertOk()
            ->assertJsonPath('data.assigned_to.id', $adminUser->id);

        $this->assertDatabaseHas('bug_reports', [
            'id'          => $report->id,
            'assigned_to' => $adminUser->id,
        ]);
    }

    public function test_admin_cannot_assign_bug_report_to_non_admin_user(): void
    {
        $token     = $this->adminToken();
        $report    = $this->createReport();
        $plainUser = User::factory()->create(['role' => 'user']);

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->putJson('/api/admin/bug-reports/assign/' . $report->id, [
                'assigned_to' => $plainUser->id,
            ])
            ->assertUnprocessable();
    }

    // ---- resolve ----

    public function test_admin_can_resolve_bug_report(): void
    {
        $token  = $this->adminToken();
        $report = $this->createReport(['status' => 'in_progress']);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->putJson('/api/admin/bug-reports/resolve/' . $report->id);

        $response->assertOk()
            ->assertJsonPath('data.status', 'resolved');

        $this->assertDatabaseHas('bug_reports', [
            'id'     => $report->id,
            'status' => 'resolved',
        ]);
        $this->assertNotNull(BugReport::find($report->id)->resolved_at);
    }

    // ---- delete ----

    public function test_admin_can_delete_bug_report(): void
    {
        $token  = $this->adminToken();
        $report = $this->createReport();

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->deleteJson('/api/admin/bug-reports/delete/' . $report->id)
            ->assertOk();

        $this->assertDatabaseMissing('bug_reports', ['id' => $report->id]);
    }

    // ---- activity log side-effects ----

    public function test_creating_bug_report_logs_activity(): void
    {
        $token = $this->adminToken();

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/admin/bug-reports/create', [
                'title'       => 'Logged Bug',
                'description' => 'Desc',
                'priority'    => 'medium',
            ]);

        $this->assertDatabaseHas('activity_logs', [
            'action'     => 'bug_report.submitted',
            'model_type' => BugReport::class,
        ]);
    }

    public function test_resolving_bug_report_logs_activity(): void
    {
        $token  = $this->adminToken();
        $report = $this->createReport();

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->putJson('/api/admin/bug-reports/resolve/' . $report->id);

        $this->assertDatabaseHas('activity_logs', [
            'action'   => 'bug_report.resolved',
            'model_id' => $report->id,
        ]);
    }
}
