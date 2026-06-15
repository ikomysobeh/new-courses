<?php

namespace Tests\Feature;

use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ReportingDatasetApiTest extends TestCase
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

    public function test_user_course_daily_returns_paginated_structure(): void
    {
        $token = $this->loginAdminAndGetToken();

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/admin/reporting/datasets/user-course-daily');

        $response->assertOk()->assertJsonStructure([
            'data',
            'meta' => ['current_page', 'per_page', 'total'],
        ]);
    }

    public function test_user_course_daily_filter_by_date_range(): void
    {
        $token = $this->loginAdminAndGetToken();

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/admin/reporting/datasets/user-course-daily?date_from=2026-01-01&date_to=2026-01-31');

        $response->assertOk();
    }

    public function test_user_course_daily_rejects_bad_date_range(): void
    {
        $token = $this->loginAdminAndGetToken();

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/admin/reporting/datasets/user-course-daily?date_from=2026-02-01&date_to=2026-01-01');

        $response->assertUnprocessable();
    }

    public function test_department_course_daily_returns_paginated_structure(): void
    {
        $token = $this->loginAdminAndGetToken();

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/admin/reporting/datasets/department-course-daily');

        $response->assertOk()->assertJsonStructure([
            'data',
            'meta' => ['current_page', 'per_page', 'total'],
        ]);
    }

    public function test_session_fact_returns_paginated_structure(): void
    {
        $token = $this->loginAdminAndGetToken();

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/admin/reporting/datasets/session-fact');

        $response->assertOk()->assertJsonStructure([
            'data',
            'meta' => ['current_page', 'per_page', 'total'],
        ]);
    }

    public function test_session_fact_filter_by_suspicious(): void
    {
        $token = $this->loginAdminAndGetToken();

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/admin/reporting/datasets/session-fact?is_suspicious=1');

        $response->assertOk();
    }

    public function test_per_page_out_of_bounds_is_rejected(): void
    {
        $token = $this->loginAdminAndGetToken();

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/admin/reporting/datasets/user-course-daily?per_page=999');

        $response->assertUnprocessable();
    }
}
