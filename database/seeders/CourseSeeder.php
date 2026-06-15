<?php

namespace Database\Seeders;

use App\Models\Course;
use App\Models\CourseAvailability;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class CourseSeeder extends Seeder
{
    public function run(): void
    {
        $adminId = User::where('role', 'admin')->value('id');

        if (! $adminId) {
            $this->command?->warn('Admin user not found. Run AdminSeeder first.');
            return;
        }

        $courses = [
            [
                'name'        => 'Leadership Excellence Program',
                'description' => 'A comprehensive classroom-based programme covering the pillars of effective leadership, team motivation, and decision-making under pressure.',
                'level'       => 'Advanced',
                'duration'    => 16.0,
                'status'      => 'published',
                'privacy'     => 'public',
                'availabilities' => [
                    [
                        'start_date'               => Carbon::now()->addDays(14),
                        'end_date'                 => Carbon::now()->addDays(15),
                        'capacity'                 => 25,
                        'sessions'                 => 2,
                        'duration_weeks'           => 1,
                        'status'                   => 'active',
                        'days_of_week'             => 'Monday,Tuesday',
                        'session_time_shift_1'     => '09:00:00',
                        'session_duration_minutes' => 240,
                    ],
                ],
            ],
            [
                'name'        => 'Effective Communication Workshop',
                'description' => 'Interactive workshop designed to sharpen verbal, written, and presentation skills for all levels of staff.',
                'level'       => 'Intermediate',
                'duration'    => 8.0,
                'status'      => 'published',
                'privacy'     => 'public',
                'availabilities' => [
                    [
                        'start_date'               => Carbon::now()->addDays(7),
                        'end_date'                 => Carbon::now()->addDays(7),
                        'capacity'                 => 20,
                        'sessions'                 => 1,
                        'duration_weeks'           => 1,
                        'status'                   => 'active',
                        'days_of_week'             => 'Wednesday',
                        'session_time_shift_1'     => '10:00:00',
                        'session_duration_minutes' => 480,
                    ],
                ],
            ],
            [
                'name'        => 'Sales Mastery Bootcamp',
                'description' => 'An intensive multi-day bootcamp covering the entire sales funnel from prospecting to closing and post-sale relationship management.',
                'level'       => 'Intermediate',
                'duration'    => 24.0,
                'status'      => 'published',
                'privacy'     => 'public',
                'availabilities' => [
                    [
                        'start_date'               => Carbon::now()->addDays(21),
                        'end_date'                 => Carbon::now()->addDays(23),
                        'capacity'                 => 30,
                        'sessions'                 => 3,
                        'duration_weeks'           => 1,
                        'status'                   => 'active',
                        'days_of_week'             => 'Monday,Tuesday,Wednesday',
                        'session_time_shift_1'     => '08:30:00',
                        'session_duration_minutes' => 480,
                    ],
                ],
            ],
            [
                'name'        => 'HR Fundamentals for Line Managers',
                'description' => 'Equips line managers with the essential HR knowledge needed to manage their teams within legal and company guidelines.',
                'level'       => 'Beginner',
                'duration'    => 8.0,
                'status'      => 'published',
                'privacy'     => 'public',
                'availabilities' => [
                    [
                        'start_date'               => Carbon::now()->addDays(10),
                        'end_date'                 => Carbon::now()->addDays(10),
                        'capacity'                 => 20,
                        'sessions'                 => 1,
                        'status'                   => 'active',
                        'days_of_week'             => 'Thursday',
                        'session_time_shift_1'     => '09:00:00',
                        'session_duration_minutes' => 480,
                    ],
                ],
            ],
            [
                'name'        => 'Financial Acumen for Non-Finance Managers',
                'description' => 'Builds financial literacy so managers can read reports, manage budgets, and contribute to financial discussions.',
                'level'       => 'Intermediate',
                'duration'    => 12.0,
                'status'      => 'published',
                'privacy'     => 'public',
                'availabilities' => [
                    [
                        'start_date'               => Carbon::now()->addDays(30),
                        'end_date'                 => Carbon::now()->addDays(31),
                        'capacity'                 => 20,
                        'sessions'                 => 2,
                        'status'                   => 'active',
                        'days_of_week'             => 'Sunday,Monday',
                        'session_time_shift_1'     => '09:00:00',
                        'session_duration_minutes' => 360,
                    ],
                ],
            ],
            [
                'name'        => 'Workplace Safety & Compliance Training',
                'description' => 'Mandatory safety and regulatory compliance training covering fire safety, first aid, and occupational health standards.',
                'level'       => 'Beginner',
                'duration'    => 6.0,
                'status'      => 'published',
                'privacy'     => 'public',
                'availabilities' => [
                    [
                        'start_date'               => Carbon::now()->addDays(5),
                        'end_date'                 => Carbon::now()->addDays(5),
                        'capacity'                 => 30,
                        'sessions'                 => 1,
                        'status'                   => 'active',
                        'days_of_week'             => 'Saturday',
                        'session_time_shift_1'     => '08:00:00',
                        'session_duration_minutes' => 360,
                    ],
                ],
            ],
        ];

        foreach ($courses as $courseData) {
            $availabilities = $courseData['availabilities'];
            unset($courseData['availabilities']);

            $course = Course::withTrashed()->updateOrCreate(
                ['name' => $courseData['name']],
                array_merge($courseData, [
                    'created_by' => $adminId,
                    'deleted_at' => null,
                ])
            );

            foreach ($availabilities as $avail) {
                CourseAvailability::updateOrCreate(
                    [
                        'course_id'  => $course->id,
                        'start_date' => $avail['start_date'],
                    ],
                    array_merge($avail, ['course_id' => $course->id])
                );
            }
        }

        $this->command?->info('Course seeded: ' . count($courses) . ' courses with availabilities.');
    }
}
