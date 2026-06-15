<?php

namespace Tests\Feature;

use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportingExportApiTest extends TestCase
{
    use RefreshDatabase;

    private function loginAdminAndGetToken(): string
    {
        $this->seed(DatabaseSeeder::class);

        $response = $this->postJson('/api/login', [
            'email'    => 'admin@newproject.test',
            'password' => env('ADMIN_INITIAL_PASSWORD', 'Admin@12345'),
        ]);

        return (string) $response->json('data.token');
    }

    public function test_export_user_course_daily_csv(): void
    {
        $token = $this->loginAdminAndGetToken();

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/admin/reporting/export/user-course-daily?export_type=csv');

        $response->assertOk();
        $this->assertStringContainsString('text/csv', $response->headers->get('Content-Type'));
    }

    public function test_export_rejects_invalid_export_type(): void
    {
        $token = $this->loginAdminAndGetToken();

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/admin/reporting/export/user-course-daily?export_type=pdf');

        $response->assertUnprocessable();
    }

    public function test_export_requires_export_type(): void
    {
        $token = $this->loginAdminAndGetToken();

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/admin/reporting/export/user-course-daily');

        $response->assertUnprocessable();
    }

    public function test_export_department_course_daily_csv(): void
    {
        $token = $this->loginAdminAndGetToken();

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/admin/reporting/export/department-course-daily?export_type=csv');

        $response->assertOk();
    }

    public function test_export_session_fact_csv(): void
    {
        $token = $this->loginAdminAndGetToken();

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/admin/reporting/export/session-fact?export_type=csv');

        $response->assertOk();
    }

    public function test_export_kpi_overview_csv(): void
    {
        $token = $this->loginAdminAndGetToken();

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/admin/reporting/export/kpi-overview?export_type=csv');

        $response->assertOk();
    }

    public function test_export_with_date_filter_succeeds(): void
    {
        $token = $this->loginAdminAndGetToken();

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/admin/reporting/export/user-course-daily?export_type=csv&date_from=2026-01-01&date_to=2026-01-31');

        $response->assertOk();
    }
}
