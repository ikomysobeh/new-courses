<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CourseAssignmentSeeder extends Seeder
{
    public function run(): void
    {
        $admin   = DB::table('users')->where('role', 'admin')->first();
        $users   = DB::table('users')->where('role', 'user')->get();
        $courses = DB::table('courses')->whereNull('deleted_at')->where('status', 'published')->get();
        $quizzes = DB::table('quizzes')->whereNull('deleted_at')->where('status', 'published')->get();

        if (! $admin || $users->isEmpty()) {
            $this->command?->warn('CourseAssignmentSeeder: missing admin or users — skipping.');
            return;
        }

        $courseAssignments = 0;
        $quizAssignments   = 0;

        foreach ($users as $userIdx => $user) {
            // Course assignments — each user gets 2–3 published courses
            if ($courses->isNotEmpty()) {
                $take = ($userIdx % 3 === 0) ? 3 : 2;
                $selectedCourses = $courses->values()
                    ->filter(fn ($c, $i) => ($i + $userIdx) % $courses->count() < $take);

                if ($selectedCourses->isEmpty()) {
                    $selectedCourses = $courses->take($take);
                }

                foreach ($selectedCourses as $course) {
                    $avail = DB::table('course_availabilities')
                        ->where('course_id', $course->id)
                        ->first();

                    $exists = DB::table('course_assignments')
                        ->where('user_id', $user->id)
                        ->where('course_id', $course->id)
                        ->exists();

                    if (! $exists) {
                        $assignedAt = now()->subDays(rand(15, 35));
                        DB::table('course_assignments')->insert([
                            'course_id'              => $course->id,
                            'user_id'                => $user->id,
                            'assigned_by'            => $admin->id,
                            'course_availability_id' => $avail?->id,
                            'assigned_at'            => $assignedAt,
                            'created_at'             => $assignedAt,
                            'updated_at'             => $assignedAt,
                        ]);
                        $courseAssignments++;
                    }
                }
            }

            // Quiz assignments — each user gets 1–2 published quizzes
            if ($quizzes->isNotEmpty()) {
                $take = ($userIdx % 2 === 0) ? 2 : 1;
                $selectedQuizzes = $quizzes->values()
                    ->filter(fn ($q, $i) => ($i + $userIdx) % $quizzes->count() < $take);

                if ($selectedQuizzes->isEmpty()) {
                    $selectedQuizzes = $quizzes->take($take);
                }

                foreach ($selectedQuizzes as $quiz) {
                    $exists = DB::table('quiz_assignments')
                        ->where('user_id', $user->id)
                        ->where('quiz_id', $quiz->id)
                        ->exists();

                    if (! $exists) {
                        $assignedAt = now()->subDays(rand(10, 25));
                        DB::table('quiz_assignments')->insert([
                            'user_id'           => $user->id,
                            'quiz_id'           => $quiz->id,
                            'assigned_by'       => $admin->id,
                            'assigned_at'       => $assignedAt,
                            'notification_sent' => 1,
                            'created_at'        => $assignedAt,
                            'updated_at'        => $assignedAt,
                        ]);
                        $quizAssignments++;
                    }
                }
            }
        }

        $this->command?->info("CourseAssignmentSeeder: {$courseAssignments} course assignments, {$quizAssignments} quiz assignments.");
    }
}
