<?php

namespace App\Services\Reporting\Aggregation;

use App\Services\Reporting\Progress\UserCourseProgressReportService;
use Illuminate\Support\Facades\DB;

/**
 * Rebuilds the reporting_user_course_progress snapshot table from live source
 * data, reusing the exact same row builder the Excel export uses so the cached
 * table and the live export can never disagree.
 */
class UserCourseProgressAggregatorService
{
    public function __construct(
        protected UserCourseProgressReportService $report,
    ) {}

    /**
     * Rebuild the whole snapshot (full refresh). Returns rows written.
     */
    public function rebuild(): int
    {
        $rows = $this->report->buildRows();
        $snapshotDate = now()->toDateString();
        $now = now();

        $payload = $rows->map(function (array $r) use ($snapshotDate, $now) {
            return [
                'user_id'               => $r['user_id'],
                'course_type'           => $r['course_type'],
                'course_id'             => $r['course_id'],
                'department_id'         => $r['department_id'],
                'user_name'             => $r['user_name'],
                'department_name'       => $r['department'] === 'N/A' ? null : $r['department'],
                'course_name'           => $r['course_name'],
                'course_beginning_date' => $r['course_beginning_date']?->toDateString(),
                'status'                => $r['status'],
                'completion_status'     => $r['completion_status'],
                'is_completed'          => $r['is_completed'],
                'days_overdue'          => $r['days_overdue'],
                'progress_percentage'   => $r['progress_percentage'],
                'started_at'            => $r['started_at']?->toDateTimeString(),
                'completed_at'          => $r['completed_at']?->toDateTimeString(),
                'deadline'              => $r['deadline']?->toDateTimeString(),
                'attention_score'       => $r['attention_score'],
                'quiz_score'            => $r['quiz_score'],
                'completion_rate'       => $r['completion_rate'],
                'learning_score'        => $r['learning_score'],
                'score_band'            => $r['score_band'],
                'compliance_status'     => $r['compliance_status'],
                'snapshot_date'         => $snapshotDate,
                'created_at'            => $now,
                'updated_at'            => $now,
            ];
        })->values()->all();

        // Full rebuild: clear then chunk-insert (upsert keeps stale rows around).
        DB::table('reporting_user_course_progress')->truncate();

        $written = 0;
        foreach (array_chunk($payload, 500) as $chunk) {
            DB::table('reporting_user_course_progress')->insert($chunk);
            $written += count($chunk);
        }

        return $written;
    }
}
