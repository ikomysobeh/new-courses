<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CourseAnalyticsSeeder extends Seeder
{
    public function run(): void
    {
        $courses = DB::table('course_onlines')->whereNull('deleted_at')->get();

        if ($courses->isEmpty()) {
            $this->command?->warn('CourseAnalyticsSeeder: no online courses found — skipping.');
            return;
        }

        $rows = 0;

        foreach ($courses as $course) {
            $enrollments = DB::table('course_online_assignments')
                ->where('course_online_id', $course->id)
                ->count();

            $progressRows = DB::table('user_course_progress')
                ->where('course_online_id', $course->id)
                ->get();

            $active    = $progressRows->where('status', 'in_progress')->count();
            $completed = $progressRows->where('status', 'completed')->count();
            $total     = $progressRows->count();

            $completionRate = $total > 0
                ? round($completed / $total * 100, 2)
                : 0.00;

            $dropoutRate = max(0, $enrollments - $total) > 0 && $enrollments > 0
                ? round(($enrollments - $total) / $enrollments * 100, 2)
                : 0.00;

            $sessionsStats = DB::table('learning_sessions')
                ->where('course_online_id', $course->id)
                ->selectRaw('
                    COALESCE(AVG(active_playback_time), 0) / 60 AS avg_duration_minutes,
                    COALESCE(AVG(video_completion_percentage), 0)  AS avg_completion_pct,
                    COALESCE(SUM(is_suspicious), 0)                AS suspicious_count
                ')
                ->first();

            DB::table('course_analytics')->updateOrInsert(
                ['course_online_id' => $course->id],
                [
                    'total_enrollments'                => $enrollments ?: $total,
                    'active_learners'                  => $active,
                    'completed_learners'               => $completed,
                    'completion_rate'                  => $completionRate,
                    'dropout_rate'                     => $dropoutRate,
                    'average_session_duration_minutes' => (int) round((float) ($sessionsStats?->avg_duration_minutes ?? rand(15, 25))),
                    'average_video_completion_rate'    => round((float) ($sessionsStats?->avg_completion_pct ?? rand(50, 80)), 2),
                    'cheating_incidents_count'         => (int) ($sessionsStats?->suspicious_count ?? 0),
                    'last_calculated_at'               => now(),
                    'created_at'                       => now(),
                    'updated_at'                       => now(),
                ]
            );
            $rows++;
        }

        $this->command?->info("CourseAnalyticsSeeder: analytics computed for {$rows} online courses.");
    }
}
