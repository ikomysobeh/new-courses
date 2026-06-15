<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ActivityLogSeeder extends Seeder
{
    public function run(): void
    {
        $users   = DB::table('users')->whereNull('deleted_at')->get();
        $courses = DB::table('course_onlines')->whereNull('deleted_at')->pluck('id')->toArray();
        $quizzes = DB::table('quizzes')->whereNull('deleted_at')->pluck('id')->toArray();
        $audios  = DB::table('audios')->whereNull('deleted_at')->pluck('id')->toArray();

        if ($users->isEmpty()) {
            $this->command?->warn('ActivityLogSeeder: no users found — skipping.');
            return;
        }

        $rows = 0;

        foreach ($users as $userIdx => $user) {
            $daysBack = 28;

            // Logins spread across the last 28 days
            for ($day = $daysBack; $day >= 1; $day -= rand(1, 3)) {
                DB::table('activity_logs')->insert([
                    'user_id'     => $user->id,
                    'description' => 'User logged into the portal',
                    'action'      => 'login',
                    'model_type'  => 'App\\Models\\User',
                    'model_id'    => $user->id,
                    'properties'  => json_encode(['ip' => '192.168.' . (($userIdx % 10) + 1) . '.' . (($user->id * 7) % 250 + 1)]),
                    'created_at'  => now()->subDays($day)->setTime(($userIdx * 3 + $day) % 14 + 7, ($userIdx * 7 + $day) % 59),
                    'updated_at'  => now()->subDays($day),
                ]);
                $rows++;
            }

            // Course started events
            if (! empty($courses)) {
                $myCourses = array_slice($courses, $userIdx % count($courses), min(2, count($courses)));
                foreach ($myCourses as $courseId) {
                    DB::table('activity_logs')->insert([
                        'user_id'     => $user->id,
                        'description' => 'User started an online course',
                        'action'      => 'course_started',
                        'model_type'  => 'App\\Models\\OnlineCourse\\CourseOnline',
                        'model_id'    => $courseId,
                        'properties'  => json_encode(['course_id' => $courseId]),
                        'created_at'  => now()->subDays(rand(15, 28)),
                        'updated_at'  => now()->subDays(rand(15, 28)),
                    ]);
                    $rows++;
                }
            }

            // Quiz attempt event
            if (! empty($quizzes)) {
                $quizId = $quizzes[$userIdx % count($quizzes)];
                DB::table('activity_logs')->insert([
                    'user_id'     => $user->id,
                    'description' => 'User attempted a quiz',
                    'action'      => 'quiz_attempted',
                    'model_type'  => 'App\\Models\\Quiz',
                    'model_id'    => $quizId,
                    'properties'  => json_encode(['quiz_id' => $quizId, 'attempt_number' => 1]),
                    'created_at'  => now()->subDays(rand(5, 15)),
                    'updated_at'  => now()->subDays(rand(5, 15)),
                ]);
                $rows++;
            }

            // Audio played event
            if (! empty($audios)) {
                $audioId = $audios[$userIdx % count($audios)];
                DB::table('activity_logs')->insert([
                    'user_id'     => $user->id,
                    'description' => 'User played an audio learning track',
                    'action'      => 'audio_played',
                    'model_type'  => 'App\\Models\\Audio',
                    'model_id'    => $audioId,
                    'properties'  => json_encode(['audio_id' => $audioId, 'listened_seconds' => rand(60, 600)]),
                    'created_at'  => now()->subDays(rand(2, 12)),
                    'updated_at'  => now()->subDays(rand(2, 12)),
                ]);
                $rows++;
            }

            // Profile update event
            DB::table('activity_logs')->insert([
                'user_id'     => $user->id,
                'description' => 'User updated their profile information',
                'action'      => 'profile_updated',
                'model_type'  => 'App\\Models\\User',
                'model_id'    => $user->id,
                'properties'  => json_encode(['fields_changed' => ['name', 'department_id']]),
                'created_at'  => now()->subDays(rand(10, 28)),
                'updated_at'  => now()->subDays(rand(10, 28)),
            ]);
            $rows++;
        }

        $this->command?->info("ActivityLogSeeder: {$rows} activity log entries for {$users->count()} users.");
    }
}
