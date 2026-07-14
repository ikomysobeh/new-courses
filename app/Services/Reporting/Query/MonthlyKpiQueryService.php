<?php

namespace App\Services\Reporting\Query;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Monthly KPI reporting (online courses), computed live from the pre-aggregated
 * reporting tables via a monthly GROUP BY. Mirrors the old project's monthly KPI
 * dashboard: per-month overview, department breakdown, and month-over-month
 * comparison. No dedicated monthly aggregate table is needed.
 */
class MonthlyKpiQueryService
{
    /**
     * Per-month KPI rows for a year (optionally a single month / filters).
     */
    public function monthlyOverview(array $filters = []): array
    {
        $year = (int) ($filters['year'] ?? now()->year);

        $rows = DB::table('reporting_learning_sessions_fact')
            ->selectRaw("
                DATE_FORMAT(session_date, '%Y-%m') as period,
                COUNT(*) as sessions,
                SUM(active_playback_time) as active_seconds,
                AVG(completion_percentage) as avg_completion_pct,
                AVG(attention_score) as avg_attention_score,
                SUM(is_suspicious) as suspicious_sessions,
                COUNT(DISTINCT user_id) as active_users
            ")
            ->whereYear('session_date', $year)
            ->when(! empty($filters['month']),            fn ($q) => $q->whereMonth('session_date', $filters['month']))
            ->when(! empty($filters['course_online_id']), fn ($q) => $q->where('course_online_id', $filters['course_online_id']))
            ->when(! empty($filters['department_id']),    fn ($q) => $q->where('department_id', $filters['department_id']))
            ->groupBy('period')
            ->orderBy('period')
            ->get();

        return $rows->map(fn ($r) => [
            'period'              => $r->period,
            'label'              => Carbon::createFromFormat('Y-m', $r->period)->format('F Y'),
            'sessions'            => (int) $r->sessions,
            'active_seconds'      => (int) $r->active_seconds,
            'active_users'        => (int) $r->active_users,
            'avg_completion_pct'  => round((float) $r->avg_completion_pct, 2),
            'avg_attention_score' => round((float) $r->avg_attention_score, 1),
            'suspicious_sessions' => (int) $r->suspicious_sessions,
        ])->values()->all();
    }

    /**
     * Per-month, per-department roll-up from the department daily table.
     */
    public function monthlyByDepartment(array $filters = []): array
    {
        $year = (int) ($filters['year'] ?? now()->year);

        $rows = DB::table('reporting_department_course_daily as r')
            ->join('departments as d', 'd.id', '=', 'r.department_id')
            ->selectRaw("
                DATE_FORMAT(r.report_date, '%Y-%m') as period,
                r.department_id,
                d.name as department_name,
                SUM(r.enrolled_users) as enrolled_users,
                SUM(r.active_users) as active_users,
                SUM(r.completed_users) as completed_users,
                AVG(r.avg_progress_percentage) as avg_progress,
                SUM(r.total_active_seconds) as total_active_seconds
            ")
            ->whereYear('r.report_date', $year)
            ->when(! empty($filters['month']),            fn ($q) => $q->whereMonth('r.report_date', $filters['month']))
            ->when(! empty($filters['department_id']),    fn ($q) => $q->where('r.department_id', $filters['department_id']))
            ->when(! empty($filters['course_online_id']), fn ($q) => $q->where('r.course_online_id', $filters['course_online_id']))
            ->groupBy('period', 'r.department_id', 'd.name')
            ->orderBy('period')
            ->orderByDesc('active_users')
            ->get();

        return $rows->map(fn ($r) => [
            'period'               => $r->period,
            'label'                => Carbon::createFromFormat('Y-m', $r->period)->format('F Y'),
            'department_id'        => (int) $r->department_id,
            'department_name'      => $r->department_name,
            'enrolled_users'       => (int) $r->enrolled_users,
            'active_users'         => (int) $r->active_users,
            'completed_users'      => (int) $r->completed_users,
            'avg_progress'         => round((float) $r->avg_progress, 2),
            'total_active_seconds' => (int) $r->total_active_seconds,
        ])->values()->all();
    }

    /**
     * Current month vs previous month, with absolute + percentage diffs.
     */
    public function monthlyComparison(array $filters = []): array
    {
        $anchor = ! empty($filters['year']) && ! empty($filters['month'])
            ? Carbon::createFromDate((int) $filters['year'], (int) $filters['month'], 1)
            : now()->startOfMonth();

        $previous = $anchor->copy()->subMonth();

        $current  = $this->monthTotals($anchor, $filters);
        $prev     = $this->monthTotals($previous, $filters);

        $metrics = ['sessions', 'active_seconds', 'active_users', 'avg_completion_pct', 'avg_attention_score', 'suspicious_sessions'];
        $diffs   = [];
        foreach ($metrics as $m) {
            $diffs[$m] = [
                'current'    => $current[$m],
                'previous'   => $prev[$m],
                'change'     => round($current[$m] - $prev[$m], 2),
                'change_pct' => $prev[$m] > 0 ? round((($current[$m] - $prev[$m]) / $prev[$m]) * 100, 2) : null,
            ];
        }

        return [
            'current_period'  => $anchor->format('Y-m'),
            'previous_period' => $previous->format('Y-m'),
            'current_label'   => $anchor->format('F Y'),
            'previous_label'  => $previous->format('F Y'),
            'metrics'         => $diffs,
        ];
    }

    /**
     * Aggregate totals for a single month.
     *
     * @return array<string, int|float>
     */
    private function monthTotals(Carbon $month, array $filters): array
    {
        $row = DB::table('reporting_learning_sessions_fact')
            ->selectRaw('
                COUNT(*) as sessions,
                SUM(active_playback_time) as active_seconds,
                COUNT(DISTINCT user_id) as active_users,
                AVG(completion_percentage) as avg_completion_pct,
                AVG(attention_score) as avg_attention_score,
                SUM(is_suspicious) as suspicious_sessions
            ')
            ->whereYear('session_date', $month->year)
            ->whereMonth('session_date', $month->month)
            ->when(! empty($filters['course_online_id']), fn ($q) => $q->where('course_online_id', $filters['course_online_id']))
            ->when(! empty($filters['department_id']),    fn ($q) => $q->where('department_id', $filters['department_id']))
            ->first();

        return [
            'sessions'            => (int) ($row->sessions ?? 0),
            'active_seconds'      => (int) ($row->active_seconds ?? 0),
            'active_users'        => (int) ($row->active_users ?? 0),
            'avg_completion_pct'  => round((float) ($row->avg_completion_pct ?? 0), 2),
            'avg_attention_score' => round((float) ($row->avg_attention_score ?? 0), 1),
            'suspicious_sessions' => (int) ($row->suspicious_sessions ?? 0),
        ];
    }
}
