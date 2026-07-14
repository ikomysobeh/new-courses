<?php

namespace App\Services\Reporting;

use App\Services\Reporting\Aggregation\DepartmentCourseDailyAggregatorService;
use App\Services\Reporting\Aggregation\LearningSessionFactAggregatorService;
use App\Services\Reporting\Aggregation\UserCourseDailyAggregatorService;
use App\Services\Reporting\Aggregation\UserCourseProgressAggregatorService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class ReportingRefreshService
{
    public function __construct(
        protected UserCourseDailyAggregatorService     $userDaily,
        protected DepartmentCourseDailyAggregatorService $deptDaily,
        protected LearningSessionFactAggregatorService $sessionFact,
        protected UserCourseProgressAggregatorService  $userCourseProgress,
    ) {}

    /**
     * Rebuild the User Course Progress snapshot table (online + traditional).
     */
    public function refreshUserCourseProgress(): array
    {
        return $this->runWithLog(
            fn () => $this->userCourseProgress->rebuild(),
            'user-course-progress'
        );
    }

    /**
     * Refresh all reporting tables for a single date (previous day default).
     */
    public function refreshDaily(Carbon $date): array
    {
        return $this->runWithLog(function () use ($date) {
            $rows = 0;
            $rows += $this->sessionFact->backfillByDate($date);
            $rows += $this->userDaily->aggregateForDate($date);
            $rows += $this->deptDaily->aggregateForDate($date);
            return $rows;
        }, 'daily', $date->toDateString());
    }

    /**
     * Refresh all reporting tables for a date range.
     */
    public function refreshDateRange(Carbon $from, Carbon $to): array
    {
        return $this->runWithLog(function () use ($from, $to) {
            $rows = 0;
            $rows += $this->sessionFact->backfillDateRange($from, $to);
            $rows += $this->userDaily->aggregateForDateRange($from, $to);
            $rows += $this->deptDaily->aggregateForDateRange($from, $to);
            return $rows;
        }, 'date-range', null, $from->toDateString() . ' to ' . $to->toDateString());
    }

    /**
     * Full rebuild — backfills everything from the earliest session record.
     */
    public function refreshFull(): array
    {
        return $this->runWithLog(function () {
            $earliest = DB::table('learning_sessions')
                ->whereNotNull('session_end')
                ->min('session_start');

            if (! $earliest) {
                return 0;
            }

            $from = Carbon::parse($earliest)->startOfDay();
            $to   = Carbon::yesterday()->endOfDay();

            $rows = 0;
            $rows += $this->sessionFact->backfillDateRange($from, $to);
            $rows += $this->userDaily->aggregateForDateRange($from, $to);
            $rows += $this->deptDaily->aggregateForDateRange($from, $to);

            return $rows;
        }, 'full');
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    private function runWithLog(callable $work, string $type, ?string $reportDate = null, ?string $rangeLabel = null): array
    {
        $start = microtime(true);

        try {
            $rowsWritten = $work();
            $duration    = (int) round(microtime(true) - $start);

            $this->writeLog('reporting_user_course_daily', $reportDate, $duration, $rowsWritten, 'success');

            Log::info("ReportingRefreshService [{$type}] completed", [
                'date'         => $rangeLabel ?? $reportDate,
                'rows_written' => $rowsWritten,
                'duration_sec' => $duration,
            ]);

            return [
                'status'       => 'success',
                'type'         => $type,
                'rows_written' => $rowsWritten,
                'duration_sec' => $duration,
            ];
        } catch (Throwable $e) {
            $duration = (int) round(microtime(true) - $start);

            $this->writeLog('reporting_user_course_daily', $reportDate, $duration, 0, 'failed', $e->getMessage());

            Log::error("ReportingRefreshService [{$type}] failed", [
                'error'   => $e->getMessage(),
                'date'    => $rangeLabel ?? $reportDate,
                'duration_sec' => $duration,
            ]);

            throw $e;
        }
    }

    private function writeLog(
        string  $table,
        ?string $reportDate,
        int     $duration,
        int     $rowsWritten,
        string  $status,
        ?string $errorMessage = null
    ): void {
        DB::table('reporting_refresh_log')->insert([
            'report_table'     => $table,
            'report_date'      => $reportDate,
            'refreshed_at'     => now(),
            'duration_seconds' => $duration,
            'rows_written'     => $rowsWritten,
            'status'           => $status,
            'error_message'    => $errorMessage,
            'created_at'       => now(),
        ]);
    }
}
