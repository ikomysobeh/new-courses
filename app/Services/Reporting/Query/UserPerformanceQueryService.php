<?php

namespace App\Services\Reporting\Query;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Comprehensive per-user performance report for online courses.
 *
 * Combines, per user: online-course assignments, course progress, learning
 * session engagement (active time, attention, suspicious sessions) and quiz
 * performance. Derived ratings (performance_rating, risk_level) are computed
 * in UserPerformanceResource from these raw aggregates.
 */
class UserPerformanceQueryService
{
    public function baseQuery(array $filters = []): Builder
    {
        $courseId = $filters['course_online_id'] ?? null;
        $dateFrom = $filters['date_from'] ?? null;
        $dateTo   = $filters['date_to'] ?? null;

        // Assignments per user (optionally scoped to a course)
        $assignSub = DB::table('course_online_assignments')
            ->selectRaw('user_id, COUNT(*) as total_assignments')
            ->when($courseId, fn ($q) => $q->where('course_online_id', $courseId))
            ->groupBy('user_id');

        // Course progress per user
        $progressSub = DB::table('user_course_progress')
            ->selectRaw("user_id,
                SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_courses,
                SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as in_progress_courses,
                AVG(progress_percentage) as avg_progress")
            ->when($courseId, fn ($q) => $q->where('course_online_id', $courseId))
            ->groupBy('user_id');

        // Learning session engagement per user
        $sessionSub = DB::table('learning_sessions')
            ->selectRaw('user_id,
                COUNT(*) as sessions_count,
                SUM(active_playback_time) as total_active_seconds,
                AVG(attention_score) as avg_attention,
                SUM(is_suspicious) as suspicious_sessions')
            ->when($courseId, fn ($q) => $q->where('course_online_id', $courseId))
            ->when($dateFrom, fn ($q) => $q->whereDate('session_start', '>=', $dateFrom))
            ->when($dateTo, fn ($q) => $q->whereDate('session_start', '<=', $dateTo))
            ->groupBy('user_id');

        // Quiz performance per user
        $quizSub = DB::table('quiz_attempts as a')
            ->join('quizzes as q', 'q.id', '=', 'a.quiz_id')
            ->selectRaw('a.user_id,
                COUNT(*) as quiz_attempts_count,
                SUM(a.passed) as quiz_passed_count,
                AVG(CASE WHEN q.total_points > 0 THEN COALESCE(a.total_score, a.score) / q.total_points * 100 ELSE 0 END) as avg_quiz_pct')
            ->whereNotNull('a.completed_at')
            ->when($dateFrom, fn ($qq) => $qq->whereDate('a.completed_at', '>=', $dateFrom))
            ->when($dateTo, fn ($qq) => $qq->whereDate('a.completed_at', '<=', $dateTo))
            ->groupBy('a.user_id');

        $q = DB::table('users as u')
            ->leftJoin('departments as d', 'd.id', '=', 'u.department_id')
            ->leftJoinSub($assignSub, 'asg', 'asg.user_id', '=', 'u.id')
            ->leftJoinSub($progressSub, 'prog', 'prog.user_id', '=', 'u.id')
            ->leftJoinSub($sessionSub, 'sess', 'sess.user_id', '=', 'u.id')
            ->leftJoinSub($quizSub, 'qz', 'qz.user_id', '=', 'u.id')
            ->where('u.role', 'user')
            ->whereNull('u.deleted_at')
            ->select(
                'u.id as user_id',
                'u.name as user_name',
                'u.email as user_email',
                'd.id as department_id',
                'd.name as department_name',
                DB::raw('COALESCE(asg.total_assignments, 0) as total_assignments'),
                DB::raw('COALESCE(prog.completed_courses, 0) as completed_courses'),
                DB::raw('COALESCE(prog.in_progress_courses, 0) as in_progress_courses'),
                DB::raw('COALESCE(prog.avg_progress, 0) as avg_progress'),
                DB::raw('COALESCE(sess.sessions_count, 0) as sessions_count'),
                DB::raw('COALESCE(sess.total_active_seconds, 0) as total_active_seconds'),
                DB::raw('COALESCE(sess.avg_attention, 0) as avg_attention'),
                DB::raw('COALESCE(sess.suspicious_sessions, 0) as suspicious_sessions'),
                DB::raw('COALESCE(qz.quiz_attempts_count, 0) as quiz_attempts_count'),
                DB::raw('COALESCE(qz.quiz_passed_count, 0) as quiz_passed_count'),
                DB::raw('COALESCE(qz.avg_quiz_pct, 0) as avg_quiz_pct')
            );

        if (! empty($filters['department_id'])) {
            $q->where('u.department_id', $filters['department_id']);
        }
        if (! empty($filters['user_id'])) {
            $q->where('u.id', $filters['user_id']);
        }

        return $q;
    }

    public function query(array $filters = [], int $perPage = 25): LengthAwarePaginator
    {
        return $this->baseQuery($filters)
            ->orderByDesc('avg_progress')
            ->paginate($perPage);
    }
}
