<?php

namespace App\Services\Dashboard;

use App\Models\Audio;
use App\Models\Course;
use App\Models\CourseOnline;
use App\Models\Quiz;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class AdminDashboardService
{
    /**
     * Build the system-wide admin dashboard payload.
     */
    public function build(User $admin): array
    {
        return [
            'admin' => [
                'id'   => $admin->id,
                'name' => $admin->name,
            ],
            'stats'              => $this->stats(),
            'recent_activity'    => $this->recentActivity(),
            'recent_enrollments' => $this->recentEnrollments(),
            'top_courses'        => $this->topCourses(),
        ];
    }

    /**
     * Headline KPI cards.
     */
    private function stats(): array
    {
        $totalCourses = Course::query()->where('status', 'published')->count()
            + CourseOnline::query()->where('status', 'published')->count();

        $totalUsers = User::query()->where('role', 'user')->count();

        // Active sessions = learning sessions with activity in the last 7 days.
        $activeSessions = DB::table('learning_sessions')
            ->where('session_start', '>=', now()->subDays(7))
            ->count();

        return [
            'total_courses'   => $totalCourses,
            'total_users'     => $totalUsers,
            'active_sessions' => $activeSessions,
            'completion_rate' => $this->completionRate(),
        ];
    }

    /**
     * Overall completion rate across online + traditional courses.
     */
    private function completionRate(): float
    {
        $regTotal = DB::table('course_registrations')->count();
        $regDone  = DB::table('course_registrations')->where('status', 'completed')->count();

        $ucpTotal = DB::table('user_course_progress')->count();
        $ucpDone  = DB::table('user_course_progress')->where('status', 'completed')->count();

        $denominator = $regTotal + $ucpTotal;
        if ($denominator === 0) {
            return 0.0;
        }

        return round(($regDone + $ucpDone) / $denominator * 100, 1);
    }

    /**
     * Recent activity feed from the activity log.
     */
    private function recentActivity(): array
    {
        $labels = [
            'login'           => 'logged in',
            'course_started'  => 'started course',
            'quiz_attempted'  => 'attempted quiz',
            'audio_played'    => 'played audio',
            'profile_updated' => 'updated profile',
        ];

        return DB::table('activity_logs as a')
            ->leftJoin('users as u', 'u.id', '=', 'a.user_id')
            ->orderByDesc('a.created_at')
            ->limit(10)
            ->get(['a.id', 'a.action', 'a.model_type', 'a.model_id', 'a.created_at', 'u.name as user'])
            ->map(fn ($r) => [
                'id'     => $r->id,
                'user'   => $r->user ?? 'Unknown',
                'avatar' => null,
                'action' => $labels[$r->action] ?? str_replace('_', ' ', (string) $r->action),
                'target' => $this->resolveTarget($r->model_type, (int) $r->model_id),
                'time'   => $r->created_at ?? null,
            ])
            ->all();
    }

    /**
     * Best-effort resolution of an activity log's subject to a human name.
     */
    private function resolveTarget(?string $modelType, int $modelId): ?string
    {
        if (empty($modelType) || $modelId <= 0) {
            return null;
        }

        return match (true) {
            str_contains($modelType, 'CourseOnline') => CourseOnline::query()->find($modelId)?->name,
            str_contains($modelType, 'Course')       => Course::query()->find($modelId)?->name,
            str_contains($modelType, 'Quiz')         => Quiz::query()->find($modelId)?->title,
            str_contains($modelType, 'Audio')        => Audio::query()->find($modelId)?->name,
            default                                   => null,
        };
    }

    /**
     * Latest online-course enrollments.
     */
    private function recentEnrollments(): array
    {
        return DB::table('course_online_assignments as coa')
            ->join('users as u', 'u.id', '=', 'coa.user_id')
            ->join('course_onlines as co', 'co.id', '=', 'coa.course_online_id')
            ->whereNull('co.deleted_at')
            ->orderByDesc('coa.assigned_at')
            ->limit(8)
            ->get(['u.name as user', 'co.name as course', 'coa.assigned_at as date'])
            ->map(fn ($r) => [
                'user'   => $r->user,
                'course' => $r->course,
                'date'   => $r->date,
            ])
            ->all();
    }

    /**
     * Top online courses by enrollment (from pre-computed analytics).
     */
    private function topCourses(): array
    {
        return DB::table('course_analytics as ca')
            ->join('course_onlines as co', 'co.id', '=', 'ca.course_online_id')
            ->whereNull('co.deleted_at')
            ->orderByDesc('ca.total_enrollments')
            ->limit(5)
            ->get([
                'co.name as title',
                'ca.total_enrollments as enrollments',
                'ca.completion_rate',
            ])
            ->map(fn ($r) => [
                'title'           => $r->title,
                'enrollments'     => (int) $r->enrollments,
                'completion_rate' => (float) $r->completion_rate,
            ])
            ->all();
    }
}
