<?php

namespace App\Services\Reporting\Aggregation;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class UserCourseDailyAggregatorService
{
    /**
     * Aggregate session fact data for a single date into reporting_user_course_daily.
     * Returns number of rows upserted.
     */
    public function aggregateForDate(Carbon $date): int
    {
        $reportDate = $date->toDateString();

        $rows = DB::table('reporting_learning_sessions_fact')
            ->select(
                'user_id',
                'course_online_id',
                DB::raw('COUNT(*) as sessions_count'),
                DB::raw('SUM(active_playback_time) as active_playback_time'),
                DB::raw('SUM(content_completed) as content_items_completed')
            )
            ->where('session_date', $reportDate)
            ->groupBy('user_id', 'course_online_id')
            ->get();

        if ($rows->isEmpty()) {
            return 0;
        }

        $toUpsert = $rows->map(function ($row) use ($reportDate) {
            $progressPct = DB::table('user_course_progress')
                ->where('user_id', $row->user_id)
                ->where('course_online_id', $row->course_online_id)
                ->value('progress_percentage') ?? 0;

            $departmentId = DB::table('users')
                ->where('id', $row->user_id)
                ->value('department_id');

            return [
                'user_id'                 => $row->user_id,
                'course_online_id'        => $row->course_online_id,
                'department_id'           => $departmentId,
                'report_date'             => $reportDate,
                'sessions_count'          => $row->sessions_count,
                'active_playback_time'    => $row->active_playback_time,
                'content_items_completed' => $row->content_items_completed,
                'course_progress_pct'     => $progressPct,
                'created_at'              => now(),
                'updated_at'              => now(),
            ];
        })->values()->all();

        return $this->upsertDailyRows($toUpsert);
    }

    /**
     * Aggregate for a date range (inclusive on both ends).
     * Returns total rows upserted across all dates.
     */
    public function aggregateForDateRange(Carbon $from, Carbon $to): int
    {
        $total = 0;
        $cursor = $from->copy()->startOfDay();
        $end    = $to->copy()->startOfDay();

        while ($cursor->lte($end)) {
            $total += $this->aggregateForDate($cursor->copy());
            $cursor->addDay();
        }

        return $total;
    }

    /**
     * Upsert pre-built rows array into reporting_user_course_daily.
     * Returns number of rows written.
     */
    public function upsertDailyRows(array $rows): int
    {
        if (empty($rows)) {
            return 0;
        }

        foreach (array_chunk($rows, 500) as $chunk) {
            DB::table('reporting_user_course_daily')->upsert(
                $chunk,
                ['user_id', 'course_online_id', 'report_date'],
                ['department_id', 'sessions_count', 'active_playback_time', 'content_items_completed', 'course_progress_pct', 'updated_at']
            );
        }

        return count($rows);
    }
}
