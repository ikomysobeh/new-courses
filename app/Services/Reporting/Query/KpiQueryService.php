<?php

namespace App\Services\Reporting\Query;

use Illuminate\Support\Facades\DB;

class KpiQueryService
{
    public function overview(array $filters = []): array
    {
        $from = $filters['date_from'] ?? now()->subDays(30)->toDateString();
        $to   = $filters['date_to']   ?? now()->toDateString();

        $sessions = DB::table('reporting_learning_sessions_fact')
            ->whereBetween('session_date', [$from, $to])
            ->when(! empty($filters['course_online_id']), fn ($q) => $q->where('course_online_id', $filters['course_online_id']))
            ->when(! empty($filters['department_id']),    fn ($q) => $q->where('department_id', $filters['department_id']));

        $totals = (clone $sessions)->selectRaw('
            COUNT(*) as total_sessions,
            SUM(active_playback_time) as total_active_seconds,
            AVG(completion_percentage) as avg_completion_pct,
            AVG(attention_score) as avg_attention_score,
            SUM(is_suspicious) as suspicious_sessions
        ')->first();

        $completedUsers = DB::table('user_course_progress')
            ->where('status', 'completed')
            ->when(! empty($filters['course_online_id']), fn ($q) => $q->where('course_online_id', $filters['course_online_id']))
            ->count();

        $enrolledUsers = DB::table('course_online_assignments')
            
            ->when(! empty($filters['course_online_id']), fn ($q) => $q->where('course_online_id', $filters['course_online_id']))
            ->count();

        $completionRate = $enrolledUsers > 0
            ? round(($completedUsers / $enrolledUsers) * 100, 2)
            : 0;

        return [
            'period'               => ['from' => $from, 'to' => $to],
            'total_sessions'       => (int) ($totals->total_sessions ?? 0),
            'total_active_seconds' => (int) ($totals->total_active_seconds ?? 0),
            'avg_completion_pct'   => round((float) ($totals->avg_completion_pct ?? 0), 2),
            'avg_attention_score'  => round((float) ($totals->avg_attention_score ?? 0), 1),
            'suspicious_sessions'  => (int) ($totals->suspicious_sessions ?? 0),
            'enrolled_users'       => $enrolledUsers,
            'completed_users'      => $completedUsers,
            'completion_rate'      => $completionRate,
        ];
    }

    public function trends(array $filters = []): array
    {
        $from = $filters['date_from'] ?? now()->subDays(30)->toDateString();
        $to   = $filters['date_to']   ?? now()->toDateString();

        return DB::table('reporting_learning_sessions_fact')
            ->selectRaw('
                session_date,
                COUNT(*) as sessions,
                SUM(active_playback_time) as active_seconds,
                AVG(completion_percentage) as avg_completion_pct,
                AVG(attention_score) as avg_attention_score
            ')
            ->whereBetween('session_date', [$from, $to])
            ->when(! empty($filters['course_online_id']), fn ($q) => $q->where('course_online_id', $filters['course_online_id']))
            ->when(! empty($filters['department_id']),    fn ($q) => $q->where('department_id', $filters['department_id']))
            ->groupBy('session_date')
            ->orderBy('session_date')
            ->get()
            ->map(fn ($r) => [
                'date'              => $r->session_date,
                'sessions'          => (int)   $r->sessions,
                'active_seconds'    => (int)   $r->active_seconds,
                'avg_completion_pct'=> round((float) $r->avg_completion_pct, 2),
                'avg_attention_score' => round((float) $r->avg_attention_score, 1),
            ])
            ->values()
            ->all();
    }
}
