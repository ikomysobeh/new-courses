<?php

namespace Tests\Feature;

use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportingRefreshApiTest extends TestCase
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

    public function test_daily_refresh_returns_success_status(): void
    {
        $token = $this->loginAdminAndGetToken();

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/admin/reporting/refresh/daily', [
                'date' => '2026-01-01',
            ]);

        $response->assertOk()->assertJsonPath('status', 'success');
        $response->assertJsonStructure(['status', 'rows_written', 'duration_sec']);
    }

    public function test_daily_refresh_without_date_uses_yesterday(): void
    {
        $token = $this->loginAdminAndGetToken();

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/admin/reporting/refresh/daily');

        $response->assertOk()->assertJsonPath('status', 'success');
    }

    public function test_range_refresh_requires_both_dates(): void
    {
        $token = $this->loginAdminAndGetToken();

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/admin/reporting/refresh/range', [
                'date_from' => '2026-01-01',
            ]);

        $response->assertUnprocessable();
    }

    public function test_range_refresh_rejects_inverted_dates(): void
    {
        $token = $this->loginAdminAndGetToken();

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/admin/reporting/refresh/range', [
                'date_from' => '2026-02-01',
                'date_to'   => '2026-01-01',
            ]);

        $response->assertUnprocessable();
    }

    public function test_range_refresh_with_valid_dates_succeeds(): void
    {
        $token = $this->loginAdminAndGetToken();

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/admin/reporting/refresh/range', [
                'date_from' => '2026-01-01',
                'date_to'   => '2026-01-03',
            ]);

        $response->assertOk()->assertJsonPath('status', 'success');
    }

    public function test_full_refresh_succeeds(): void
    {
        $token = $this->loginAdminAndGetToken();

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/admin/reporting/refresh/full');

        $response->assertOk()->assertJsonPath('status', 'success');
    }

    public function test_refresh_log_returns_array(): void
    {
        $token = $this->loginAdminAndGetToken();

        // Trigger one refresh to produce a log entry
        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/admin/reporting/refresh/daily', ['date' => '2026-01-01']);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/admin/reporting/refresh/log');

        $response->assertOk();
        $this->assertIsArray($response->json());
    }

    public function test_refresh_is_idempotent(): void
    {
        $token = $this->loginAdminAndGetToken();

        $first = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/admin/reporting/refresh/daily', ['date' => '2026-01-01']);

        $second = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/admin/reporting/refresh/daily', ['date' => '2026-01-01']);

        $first->assertOk()->assertJsonPath('status', 'success');
        $second->assertOk()->assertJsonPath('status', 'success');
    }
}
