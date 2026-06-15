<?php

namespace Tests\Unit\Reporting;

use App\Services\Reporting\Aggregation\LearningSessionFactAggregatorService;
use App\Services\Reporting\Aggregation\UserCourseDailyAggregatorService;
use App\Services\Reporting\Aggregation\DepartmentCourseDailyAggregatorService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ReportingAggregatorServiceTest extends TestCase
{
    use RefreshDatabase;

    // -----------------------------------------------------------------------
    // Helpers
    // -----------------------------------------------------------------------

    private function seedMinimalData(): void
    {
        // Department
        DB::table('departments')->insertOrIgnore([
            'id' => 1, 'name' => 'IT', 'slug' => 'it', 'created_at' => now(), 'updated_at' => now(),
        ]);

        // User
        DB::table('users')->insertOrIgnore([
            'id' => 1, 'name' => 'Alice', 'email' => 'alice@test.com',
            'password' => bcrypt('pass'), 'role' => 'user', 'department_id' => 1,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        // Course (no slug column; created_by is required)
        DB::table('course_onlines')->insertOrIgnore([
            'id' => 1, 'name' => 'PHP Basics', 'created_by' => 1,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        // Learning session (content_id nullable — skip module_contents seeding)
        DB::table('learning_sessions')->insertOrIgnore([
            'id'                          => 1,
            'user_id'                     => 1,
            'course_online_id'            => 1,
            'content_id'                  => null,
            'session_start'               => '2026-01-05 10:00:00',
            'session_end'                 => '2026-01-05 10:20:00',
            'active_playback_time'        => 900,
            'wall_clock_seconds'          => 1200,
            'video_completion_percentage' => 75,
            'attention_score'             => 85,
            'is_suspicious'              => 0,
            'skip_count'                 => 1,
            'seek_count'                 => 2,
            'replay_count'               => 0,
            'pause_count'                => 3,
            'created_at'                 => now(),
            'updated_at'                 => now(),
        ]);

        // Enrollment
        DB::table('course_online_assignments')->insertOrIgnore([
            'id' => 1, 'user_id' => 1, 'course_online_id' => 1,
            'assigned_by' => 1, 'assigned_at' => now(),
            'created_at' => now(), 'updated_at' => now(),
        ]);

        // Progress
        DB::table('user_course_progress')->insertOrIgnore([
            'id' => 1, 'user_id' => 1, 'course_online_id' => 1,
            'progress_percentage' => 75, 'status' => 'in_progress',
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    // -----------------------------------------------------------------------
    // LearningSessionFactAggregatorService
    // -----------------------------------------------------------------------

    public function test_backfill_by_date_inserts_fact_row(): void
    {
        $this->seedMinimalData();

        $service = app(LearningSessionFactAggregatorService::class);
        $count   = $service->backfillByDate(Carbon::parse('2026-01-05'));

        $this->assertSame(1, $count);
        $this->assertDatabaseHas('reporting_learning_sessions_fact', [
            'session_id' => 1,
            'user_id'    => 1,
        ]);
    }

    public function test_backfill_is_idempotent_for_session_fact(): void
    {
        $this->seedMinimalData();

        $service = app(LearningSessionFactAggregatorService::class);
        $service->backfillByDate(Carbon::parse('2026-01-05'));
        $service->backfillByDate(Carbon::parse('2026-01-05'));

        $this->assertSame(1, DB::table('reporting_learning_sessions_fact')->count());
    }

    public function test_backfill_skips_open_sessions(): void
    {
        $this->seedMinimalData();

        // Create an open session (no session_end)
        DB::table('learning_sessions')->insert([
            'id'                  => 99,
            'user_id'             => 1,
            'course_online_id'    => 1,
            'content_id'          => null,
            'session_start'       => '2026-01-05 12:00:00',
            'session_end'         => null,
            'active_playback_time'=> 0,
            'wall_clock_seconds'  => 0,
            'created_at'          => now(),
            'updated_at'          => now(),
        ]);

        $service = app(LearningSessionFactAggregatorService::class);
        $count   = $service->backfillByDate(Carbon::parse('2026-01-05'));

        // Only the closed session (id=1) should have been written
        $this->assertSame(1, $count);
        $this->assertDatabaseMissing('reporting_learning_sessions_fact', ['session_id' => 99]);
    }

    // -----------------------------------------------------------------------
    // UserCourseDailyAggregatorService
    // -----------------------------------------------------------------------

    public function test_user_course_daily_aggregates_for_date(): void
    {
        $this->seedMinimalData();

        // First seed fact table
        app(LearningSessionFactAggregatorService::class)->backfillByDate(Carbon::parse('2026-01-05'));

        $service = app(UserCourseDailyAggregatorService::class);
        $count   = $service->aggregateForDate(Carbon::parse('2026-01-05'));

        $this->assertGreaterThan(0, $count);
        $this->assertDatabaseHas('reporting_user_course_daily', [
            'user_id'         => 1,
            'course_online_id'=> 1,
            'report_date'     => '2026-01-05',
        ]);
    }

    public function test_user_course_daily_is_idempotent(): void
    {
        $this->seedMinimalData();
        app(LearningSessionFactAggregatorService::class)->backfillByDate(Carbon::parse('2026-01-05'));

        $service = app(UserCourseDailyAggregatorService::class);
        $service->aggregateForDate(Carbon::parse('2026-01-05'));
        $service->aggregateForDate(Carbon::parse('2026-01-05'));

        $this->assertSame(1, DB::table('reporting_user_course_daily')->count());
    }

    // -----------------------------------------------------------------------
    // DepartmentCourseDailyAggregatorService
    // -----------------------------------------------------------------------

    public function test_department_course_daily_aggregates_for_date(): void
    {
        $this->seedMinimalData();
        app(LearningSessionFactAggregatorService::class)->backfillByDate(Carbon::parse('2026-01-05'));

        $service = app(DepartmentCourseDailyAggregatorService::class);
        $count   = $service->aggregateForDate(Carbon::parse('2026-01-05'));

        $this->assertGreaterThan(0, $count);
        $this->assertDatabaseHas('reporting_department_course_daily', [
            'department_id'   => 1,
            'course_online_id'=> 1,
            'report_date'     => '2026-01-05',
        ]);
    }

    public function test_department_course_daily_is_idempotent(): void
    {
        $this->seedMinimalData();
        app(LearningSessionFactAggregatorService::class)->backfillByDate(Carbon::parse('2026-01-05'));

        $service = app(DepartmentCourseDailyAggregatorService::class);
        $service->aggregateForDate(Carbon::parse('2026-01-05'));
        $service->aggregateForDate(Carbon::parse('2026-01-05'));

        $this->assertSame(1, DB::table('reporting_department_course_daily')->count());
    }
}
