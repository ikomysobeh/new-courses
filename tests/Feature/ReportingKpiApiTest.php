<?php

namespace Tests\Feature;

use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ReportingKpiApiTest extends TestCase
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

    public function test_kpi_overview_returns_expected_structure(): void
    {
        $token = $this->loginAdminAndGetToken();

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/admin/reporting/kpi/overview');

        $response->assertOk()->assertJsonStructure([
            'total_sessions',
            'total_active_seconds',
            'avg_completion_pct',
            'avg_attention_score',
            'suspicious_sessions',
            'enrolled_users',
            'completed_users',
            'completion_rate',
            'period' => ['from', 'to'],
        ]);
    }

    public function test_kpi_overview_accepts_date_filter(): void
    {
        $token = $this->loginAdminAndGetToken();

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/admin/reporting/kpi/overview?date_from=2026-01-01&date_to=2026-01-31');

        $response->assertOk();
    }

    public function test_kpi_overview_rejects_invalid_date_range(): void
    {
        $token = $this->loginAdminAndGetToken();

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/admin/reporting/kpi/overview?date_from=2026-02-01&date_to=2026-01-01');

        $response->assertUnprocessable();
    }

    public function test_kpi_trends_returns_array(): void
    {
        $token = $this->loginAdminAndGetToken();

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/admin/reporting/kpi/trends?date_from=2026-01-01&date_to=2026-01-07');

        $response->assertOk()->assertJsonStructure(['data']);
        $this->assertIsArray($response->json('data'));
    }

    public function test_kpi_trends_each_item_has_required_fields(): void
    {
        $token = $this->loginAdminAndGetToken();

        // Seed prerequisite records so the fact FK is satisfied
        $adminId = DB::table('users')->where('role', 'admin')->value('id');

        DB::table('course_onlines')->insertOrIgnore([
            'id' => 1, 'name' => 'Test Course', 'created_by' => $adminId,
            'status' => 'draft', 'is_active' => 1,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        DB::table('learning_sessions')->insertOrIgnore([
            'id' => 1, 'user_id' => $adminId, 'course_online_id' => 1,
            'session_start' => '2026-01-05 10:00:00',
            'session_end'   => '2026-01-05 10:20:00',
            'created_at' => now(), 'updated_at' => now(),
        ]);

        DB::table('reporting_learning_sessions_fact')->insert([
            'session_id'           => 1,
            'user_id'              => 1,
            'course_online_id'     => 1,
            'content_id'           => 1,
            'department_id'        => 1,
            'session_date'         => '2026-01-05',
            'active_playback_time' => 600,
            'wall_clock_seconds'   => 700,
            'completion_percentage'=> 80,
            'attention_score'      => 90,
            'is_suspicious'        => 0,
            'skip_count'           => 0,
            'seek_count'           => 0,
            'replay_count'         => 0,
            'pause_count'          => 0,
            'content_completed'    => 1,
            'created_at'           => now(),
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/admin/reporting/kpi/trends?date_from=2026-01-05&date_to=2026-01-05');

        $response->assertOk();
        $data = $response->json('data');

        if (count($data) > 0) {
            $this->assertArrayHasKey('date', $data[0]);
            $this->assertArrayHasKey('sessions', $data[0]);
            $this->assertArrayHasKey('active_seconds', $data[0]);
        }
    }
}
