<?php

namespace App\Services\Reporting\Aggregation;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DepartmentCourseDailyAggregatorService
{
    /**
     * Aggregate department-level daily stats for a single date.
     * Returns number of rows upserted.
     */
    public function aggregateForDate(Carbon $date): int
    {
        $reportDate = $date->toDateString();

        // Get all active enrollments grouped by department + course
        $enrollments = DB::table('course_online_assignments as coa')
            ->join('users as u', 'u.id', '=', 'coa.user_id')
            ->select(
                'u.department_id',
                'coa.course_online_id',
                DB::raw('COUNT(DISTINCT coa.user_id) as enrolled_users')
            )
            ->whereNotNull('u.department_id')
            ->groupBy('u.department_id', 'coa.course_online_id')
            ->get()
            ->keyBy(fn ($r) => $r->department_id . '_' . $r->course_online_id);

        if ($enrollments->isEmpty()) {
            return 0;
        }

        // Aggregate session activity for that date per department + course
        $activity = DB::table('reporting_learning_sessions_fact as f')
            ->join('users as u', 'u.id', '=', 'f.user_id')
            ->select(
                'u.department_id',
                'f.course_online_id',
                DB::raw('COUNT(DISTINCT f.user_id) as active_users'),
                DB::raw('SUM(f.active_playback_time) as total_active_seconds')
            )
            ->where('f.session_date', $reportDate)
            ->whereNotNull('u.department_id')
            ->groupBy('u.department_id', 'f.course_online_id')
            ->get()
            ->keyBy(fn ($r) => $r->department_id . '_' . $r->course_online_id);

        // Aggregate completed users per department + course
        $completions = DB::table('user_course_progress as p')
            ->join('users as u', 'u.id', '=', 'p.user_id')
            ->select(
                'u.department_id',
                'p.course_online_id',
                DB::raw('COUNT(*) as completed_users'),
                DB::raw('AVG(p.progress_percentage) as avg_progress_percentage')
            )
            ->where('p.status', 'completed')
            ->whereNotNull('u.department_id')
            ->groupBy('u.department_id', 'p.course_online_id')
            ->get()
            ->keyBy(fn ($r) => $r->department_id . '_' . $r->course_online_id);

        $toUpsert = [];
        foreach ($enrollments as $key => $enroll) {
            $act  = $activity->get($key);
            $comp = $completions->get($key);

            $toUpsert[] = [
                'department_id'           => $enroll->department_id,
                'course_online_id'        => $enroll->course_online_id,
                'report_date'             => $reportDate,
                'enrolled_users'          => $enroll->enrolled_users,
                'active_users'            => $act?->active_users ?? 0,
                'completed_users'         => $comp?->completed_users ?? 0,
                'avg_progress_percentage' => $comp?->avg_progress_percentage ?? 0,
                'total_active_seconds'    => $act?->total_active_seconds ?? 0,
                'created_at'              => now(),
                'updated_at'              => now(),
            ];
        }

        return $this->upsertDailyRows($toUpsert);
    }

    /**
     * Aggregate for a date range (inclusive).
     */
    public function aggregateForDateRange(Carbon $from, Carbon $to): int
    {
        $total  = 0;
        $cursor = $from->copy()->startOfDay();
        $end    = $to->copy()->startOfDay();

        while ($cursor->lte($end)) {
            $total += $this->aggregateForDate($cursor->copy());
            $cursor->addDay();
        }

        return $total;
    }

    /**
     * Upsert pre-built rows array into reporting_department_course_daily.
     */
    public function upsertDailyRows(array $rows): int
    {
        if (empty($rows)) {
            return 0;
        }

        foreach (array_chunk($rows, 500) as $chunk) {
            DB::table('reporting_department_course_daily')->upsert(
                $chunk,
                ['department_id', 'course_online_id', 'report_date'],
                ['enrolled_users', 'active_users', 'completed_users', 'avg_progress_percentage', 'total_active_seconds', 'updated_at']
            );
        }

        return count($rows);
    }
}
