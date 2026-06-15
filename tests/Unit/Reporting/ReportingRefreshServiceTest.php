<?php

namespace Tests\Unit\Reporting;

use App\Services\Reporting\ReportingRefreshService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ReportingRefreshServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_refresh_daily_returns_success_result(): void
    {
        $service = app(ReportingRefreshService::class);
        $result  = $service->refreshDaily(Carbon::parse('2026-01-01'));

        $this->assertSame('success', $result['status']);
        $this->assertArrayHasKey('rows_written', $result);
        $this->assertArrayHasKey('duration_sec', $result);
    }

    public function test_refresh_daily_writes_to_refresh_log(): void
    {
        $service = app(ReportingRefreshService::class);
        $service->refreshDaily(Carbon::parse('2026-01-01'));

        $this->assertDatabaseHas('reporting_refresh_log', [
            'status' => 'success',
        ]);
    }

    public function test_refresh_date_range_writes_log_entry(): void
    {
        $service = app(ReportingRefreshService::class);
        $service->refreshDateRange(Carbon::parse('2026-01-01'), Carbon::parse('2026-01-02'));

        $logCount = DB::table('reporting_refresh_log')->where('status', 'success')->count();
        $this->assertGreaterThan(0, $logCount);
    }

    public function test_refresh_full_succeeds_with_empty_db(): void
    {
        $service = app(ReportingRefreshService::class);
        $result  = $service->refreshFull();

        // No sessions exist, so rows_written = 0 but status should still succeed
        $this->assertSame('success', $result['status']);
    }

    public function test_refresh_daily_is_idempotent(): void
    {
        $service = app(ReportingRefreshService::class);

        $result1 = $service->refreshDaily(Carbon::parse('2026-01-01'));
        $result2 = $service->refreshDaily(Carbon::parse('2026-01-01'));

        $this->assertSame('success', $result1['status']);
        $this->assertSame('success', $result2['status']);

        // Log should have 2 entries (one per call)
        $this->assertSame(2, DB::table('reporting_refresh_log')->count());
    }
}
