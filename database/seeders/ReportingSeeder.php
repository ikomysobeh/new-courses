<?php

namespace Database\Seeders;

use App\Models\User;
use App\Services\Reporting\Aggregation\DepartmentCourseDailyAggregatorService;
use App\Services\Reporting\Aggregation\LearningSessionFactAggregatorService;
use App\Services\Reporting\Aggregation\UserCourseDailyAggregatorService;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class ReportingSeeder extends Seeder
{
    // -----------------------------------------------------------------------
    // Entry point
    // -----------------------------------------------------------------------

    public function run(): void
    {
        if (DB::table('departments')->count() === 0) {
            $this->call(DatabaseSeeder::class);
        }

        $admin = User::where('role', 'admin')->first();

        if (! $admin) {
            $this->command?->error('Admin user not found. Run DatabaseSeeder first.');
            return;
        }

        $this->command?->info('Creating 20 test users...');
        $users = $this->createUsers();

        $this->command?->info('Creating 5 courses (3 modules × 3 videos each)...');
        $courseIds = $this->createCourses($admin->id);

        $this->command?->info('Creating assignments (all users × all courses)...');
        $this->createAssignments($users, $courseIds, $admin->id);

        $this->command?->info('Creating ~350 learning sessions over 30 days...');
        $this->createLearningSessions($users, $courseIds);

        $this->command?->info('Creating course progress snapshots...');
        $this->createCourseProgress($users, $courseIds);

        $this->command?->info('Running reporting aggregations for the last 30 days...');
        $this->runAggregations();

        $this->command?->info('Seeding traditional course data (registrations, attendance, completions)...');
        $this->seedTraditionalCourses($users, $admin->id);

        $this->command?->info('Seeding quiz data (quizzes, questions, attempts, answers)...');
        $this->seedQuizData($users);

        $this->command?->info('Seeding evaluation data for department performance report...');
        $this->seedEvaluations($users);

        $this->command?->info('ReportingSeeder complete.');
    }

    // -----------------------------------------------------------------------
    // Users — 20 employees, 4 departments, 4 behavioral profiles
    // -----------------------------------------------------------------------

    private function createUsers(): array
    {
        $password = Hash::make('User@12345');

        // dept_id: 1=IT, 2=HR, 3=Finance, 4=Operations
        // profile: power | regular | struggling | inactive
        $definitions = [
            // IT (dept 1) — 5 users
            ['email' => 'ahmed.nasser@reporting.test',   'name' => 'Ahmed Nasser',   'dept' => 1, 'profile' => 'power'],
            ['email' => 'sara.ali@reporting.test',       'name' => 'Sara Ali',       'dept' => 1, 'profile' => 'power'],
            ['email' => 'khaled.omar@reporting.test',    'name' => 'Khaled Omar',    'dept' => 1, 'profile' => 'regular'],
            ['email' => 'nour.hassan@reporting.test',    'name' => 'Nour Hassan',    'dept' => 1, 'profile' => 'struggling'],
            ['email' => 'tarek.said@reporting.test',     'name' => 'Tarek Said',     'dept' => 1, 'profile' => 'inactive'],

            // HR (dept 2) — 5 users
            ['email' => 'lina.kamal@reporting.test',     'name' => 'Lina Kamal',     'dept' => 2, 'profile' => 'power'],
            ['email' => 'omar.fahmy@reporting.test',     'name' => 'Omar Fahmy',     'dept' => 2, 'profile' => 'regular'],
            ['email' => 'dina.saleh@reporting.test',     'name' => 'Dina Saleh',     'dept' => 2, 'profile' => 'regular'],
            ['email' => 'youssef.ali@reporting.test',    'name' => 'Youssef Ali',    'dept' => 2, 'profile' => 'struggling'],
            ['email' => 'mona.adel@reporting.test',      'name' => 'Mona Adel',      'dept' => 2, 'profile' => 'inactive'],

            // Finance (dept 3) — 5 users
            ['email' => 'hassan.ibrahim@reporting.test', 'name' => 'Hassan Ibrahim', 'dept' => 3, 'profile' => 'power'],
            ['email' => 'rania.mahmoud@reporting.test',  'name' => 'Rania Mahmoud',  'dept' => 3, 'profile' => 'regular'],
            ['email' => 'sherif.nabil@reporting.test',   'name' => 'Sherif Nabil',   'dept' => 3, 'profile' => 'regular'],
            ['email' => 'aya.mostafa@reporting.test',    'name' => 'Aya Mostafa',    'dept' => 3, 'profile' => 'struggling'],
            ['email' => 'islam.saad@reporting.test',     'name' => 'Islam Saad',     'dept' => 3, 'profile' => 'inactive'],

            // Operations (dept 4) — 5 users
            ['email' => 'mariam.fathy@reporting.test',   'name' => 'Mariam Fathy',   'dept' => 4, 'profile' => 'power'],
            ['email' => 'karim.hassan@reporting.test',   'name' => 'Karim Hassan',   'dept' => 4, 'profile' => 'regular'],
            ['email' => 'heba.youssef@reporting.test',   'name' => 'Heba Youssef',   'dept' => 4, 'profile' => 'regular'],
            ['email' => 'amr.gamal@reporting.test',      'name' => 'Amr Gamal',      'dept' => 4, 'profile' => 'struggling'],
            ['email' => 'noha.saber@reporting.test',     'name' => 'Noha Saber',     'dept' => 4, 'profile' => 'inactive'],
        ];

        $users = [];

        foreach ($definitions as $def) {
            $user = User::updateOrCreate(
                ['email' => $def['email']],
                [
                    'name'          => $def['name'],
                    'password'      => $password,
                    'role'          => 'user',
                    'department_id' => $def['dept'],
                ]
            );
            $user->profile = $def['profile'];
            $users[] = $user;
        }

        return $users;
    }

    // -----------------------------------------------------------------------
    // Courses — 5 courses, 3 modules × 3 videos each
    // -----------------------------------------------------------------------

    private function createCourses(int $adminId): array
    {
        $definitions = [
            ['name' => 'Excel for Business',        'level' => 'beginner',     'duration' => 120],
            ['name' => 'Presentation Skills',        'level' => 'intermediate', 'duration' => 90],
            ['name' => 'Data Analysis Basics',       'level' => 'intermediate', 'duration' => 150],
            ['name' => 'Communication & Email',      'level' => 'beginner',     'duration' => 60],
            ['name' => 'Leadership Fundamentals',    'level' => 'advanced',     'duration' => 180],
        ];

        $courseIds = [];

        foreach ($definitions as $c) {
            $course = DB::table('course_onlines')->where('name', $c['name'])->first();

            if (! $course) {
                $id = DB::table('course_onlines')->insertGetId([
                    'name'               => $c['name'],
                    'level'              => $c['level'],
                    'estimated_duration' => $c['duration'],
                    'status'             => 'published',
                    'is_active'          => 1,
                    'created_by'         => $adminId,
                    'created_at'         => now(),
                    'updated_at'         => now(),
                ]);
            } else {
                $id = $course->id;
            }

            $this->createModulesForCourse($id);
            $courseIds[] = $id;
        }

        return $courseIds;
    }

    private function createModulesForCourse(int $courseId): void
    {
        if (DB::table('course_modules')->where('course_online_id', $courseId)->exists()) {
            return;
        }

        $modules = [
            ['name' => 'Introduction',   'order' => 1],
            ['name' => 'Core Concepts',  'order' => 2],
            ['name' => 'Advanced Topics','order' => 3],
        ];

        foreach ($modules as $m) {
            $moduleId = DB::table('course_modules')->insertGetId([
                'course_online_id'   => $courseId,
                'name'               => $m['name'],
                'order_number'       => $m['order'],
                'estimated_duration' => 40,
                'has_quiz'           => 0,
                'quiz_required'      => 0,
                'created_at'         => now(),
                'updated_at'         => now(),
            ]);

            DB::table('module_contents')->insert([
                [
                    'module_id'    => $moduleId,
                    'content_type' => 'video',
                    'title'        => $m['name'] . ' - Part 1',
                    'order_number' => 1,
                    'duration'     => 600,
                    'is_required'  => 1,
                    'is_active'    => 1,
                    'created_at'   => now(),
                    'updated_at'   => now(),
                ],
                [
                    'module_id'    => $moduleId,
                    'content_type' => 'video',
                    'title'        => $m['name'] . ' - Part 2',
                    'order_number' => 2,
                    'duration'     => 720,
                    'is_required'  => 1,
                    'is_active'    => 1,
                    'created_at'   => now(),
                    'updated_at'   => now(),
                ],
                [
                    'module_id'    => $moduleId,
                    'content_type' => 'video',
                    'title'        => $m['name'] . ' - Part 3',
                    'order_number' => 3,
                    'duration'     => 540,
                    'is_required'  => 1,
                    'is_active'    => 1,
                    'created_at'   => now(),
                    'updated_at'   => now(),
                ],
            ]);
        }
    }

    // -----------------------------------------------------------------------
    // Assignments — every user × every course
    // -----------------------------------------------------------------------

    private function createAssignments(array $users, array $courseIds, int $adminId): void
    {
        foreach ($users as $user) {
            foreach ($courseIds as $courseId) {
                DB::table('course_online_assignments')->updateOrInsert(
                    ['user_id' => $user->id, 'course_online_id' => $courseId],
                    [
                        'assigned_by' => $adminId,
                        'assigned_at' => now()->subDays(30),
                        'created_at'  => now()->subDays(30),
                        'updated_at'  => now()->subDays(30),
                    ]
                );
            }
        }
    }

    // -----------------------------------------------------------------------
    // Learning sessions — 30-day window, profile-driven behavior
    // -----------------------------------------------------------------------

    private function createLearningSessions(array $users, array $courseIds): void
    {
        foreach ($users as $user) {
            switch ($user->profile) {
                case 'power':
                    $this->seedPowerUserSessions($user, $courseIds);
                    break;
                case 'regular':
                    $this->seedRegularUserSessions($user, $courseIds);
                    break;
                case 'struggling':
                    $this->seedStrugglingUserSessions($user, $courseIds);
                    break;
                // inactive — no sessions
            }
        }
    }

    // Power users: 5–7 sessions/week across all 5 courses, high attention, no suspicious
    private function seedPowerUserSessions(object $user, array $courseIds): void
    {
        // Study most days, cycling through courses
        for ($day = 30; $day >= 1; $day--) {
            // Skip ~1 random day per week
            if ($day % 7 === 3) {
                continue;
            }

            $courseId = $courseIds[($day + $user->id) % count($courseIds)];

            $activeSecs    = rand(1200, 1800);
            $completionPct = min(100, 20 + (30 - $day) * 3 + rand(0, 5));
            $attention     = rand(88, 98);

            $this->insertSession($user->id, $courseId, $day, $activeSecs, $completionPct, $attention, 0);
        }
    }

    // Regular users: 2–4 sessions/week across 2–3 courses, average attention, rare suspicious
    private function seedRegularUserSessions(object $user, array $courseIds): void
    {
        // Pick 3 courses this user focuses on
        $myCourses = array_slice($courseIds, ($user->id % 2), 3);

        for ($day = 30; $day >= 1; $day--) {
            // Active ~3 days per week
            if (! in_array($day % 7, [1, 3, 5])) {
                continue;
            }

            $courseId = $myCourses[$day % count($myCourses)];

            $activeSecs    = rand(600, 1300);
            $completionPct = min(100, 10 + (30 - $day) * 2 + rand(0, 8));
            $attention     = rand(70, 87);
            // 1 suspicious session sprinkled in around day 20
            $suspicious    = ($day === 20) ? 1 : 0;

            $this->insertSession($user->id, $courseId, $day, $activeSecs, $completionPct, $attention, $suspicious);
        }
    }

    // Struggling users: 0–2 sessions/week, low attention, occasional suspicious
    private function seedStrugglingUserSessions(object $user, array $courseIds): void
    {
        // Only 2 courses, sporadic schedule
        $myCourses = array_slice($courseIds, 0, 2);

        for ($day = 30; $day >= 1; $day--) {
            // Active only ~2 days per week
            if (! in_array($day % 7, [2, 6])) {
                continue;
            }

            $courseId = $myCourses[$day % count($myCourses)];

            $activeSecs    = rand(150, 600);
            $completionPct = min(100, 5 + (30 - $day) + rand(0, 5));
            $attention     = rand(38, 65);
            $suspicious    = ($day % 10 === 0) ? 1 : 0;

            $this->insertSession($user->id, $courseId, $day, $activeSecs, $completionPct, $attention, $suspicious);
        }
    }

    private function insertSession(
        int $userId,
        int $courseId,
        int $daysAgo,
        int $activeSecs,
        float $completionPct,
        int $attention,
        int $suspicious
    ): void {
        $start = Carbon::now()->subDays($daysAgo)->setTime(rand(8, 18), rand(0, 59), 0);
        $end   = $start->copy()->addSeconds($activeSecs + rand(60, 300));

        $exists = DB::table('learning_sessions')
            ->where('user_id', $userId)
            ->where('course_online_id', $courseId)
            ->whereDate('session_start', $start->toDateString())
            ->exists();

        if ($exists) {
            return;
        }

        DB::table('learning_sessions')->insert([
            'user_id'                     => $userId,
            'course_online_id'            => $courseId,
            'content_id'                  => null,
            'content_type'                => 'video',
            'session_start'               => $start,
            'session_end'                 => $end,
            'last_progress_at'            => $end,
            'active_playback_time'        => $activeSecs,
            'wall_clock_seconds'          => $activeSecs + rand(60, 300),
            'video_completion_percentage' => $completionPct,
            'attention_score'             => $attention,
            'is_suspicious'               => $suspicious,
            'skip_count'                  => rand(0, 5),
            'seek_count'                  => rand(0, 8),
            'replay_count'                => rand(0, 3),
            'pause_count'                 => rand(0, 10),
            'speed_changes'               => rand(0, 2),
            'fullscreen_count'            => rand(0, 3),
            'created_at'                  => $start,
            'updated_at'                  => $end,
        ]);
    }

    // -----------------------------------------------------------------------
    // Course progress — final snapshot per user × course
    // -----------------------------------------------------------------------

    private function createCourseProgress(array $users, array $courseIds): void
    {
        foreach ($users as $user) {
            foreach ($courseIds as $index => $courseId) {
                [$pct, $status] = $this->resolveProgress($user->profile, $index);

                if ($pct === null) {
                    continue; // inactive users: no progress row
                }

                DB::table('user_course_progress')->updateOrInsert(
                    ['user_id' => $user->id, 'course_online_id' => $courseId],
                    [
                        'progress_percentage'     => $pct,
                        'status'                  => $status,
                        'total_content_items'     => 9, // 3 modules × 3 videos
                        'completed_content_items' => (int) round($pct / 100 * 9),
                        'started_at'              => now()->subDays(30),
                        'completed_at'            => $status === 'completed' ? now()->subDays(rand(1, 5)) : null,
                        'last_accessed_at'        => now()->subDays(rand(0, 3)),
                        'created_at'              => now()->subDays(30),
                        'updated_at'              => now(),
                    ]
                );
            }
        }
    }

    private function resolveProgress(string $profile, int $courseIndex): array
    {
        return match ($profile) {
            // Power users: first 3 courses completed, last 2 near-complete
            'power' => match (true) {
                $courseIndex <= 2 => [100.00, 'completed'],
                default           => [rand(80, 95), 'in_progress'],
            },
            // Regular users: first course done, rest in progress
            'regular' => match (true) {
                $courseIndex === 0 => [100.00, 'completed'],
                $courseIndex <= 2  => [rand(40, 75), 'in_progress'],
                default            => [rand(10, 35), 'in_progress'],
            },
            // Struggling users: low progress on first 2 only
            'struggling' => match (true) {
                $courseIndex === 0 => [rand(15, 35), 'in_progress'],
                $courseIndex === 1 => [rand(5, 20), 'in_progress'],
                default            => [null, 'not_started'],
            },
            // Inactive users: no rows
            default => [null, 'not_started'],
        };
    }

    // -----------------------------------------------------------------------
    // Aggregations — ETL for the full 30-day window
    // -----------------------------------------------------------------------

    private function runAggregations(): void
    {
        $sessionFact = app(LearningSessionFactAggregatorService::class);
        $userDaily   = app(UserCourseDailyAggregatorService::class);
        $deptDaily   = app(DepartmentCourseDailyAggregatorService::class);

        $sessionFactTotal = 0;
        $userDailyTotal   = 0;
        $deptDailyTotal   = 0;

        for ($daysAgo = 30; $daysAgo >= 0; $daysAgo--) {
            $date = Carbon::today()->subDays($daysAgo);

            $sessionFactTotal += $sessionFact->backfillByDate($date);
            $userDailyTotal   += $userDaily->aggregateForDate($date);
            $deptDailyTotal   += $deptDaily->aggregateForDate($date);
        }

        $this->command?->info(sprintf(
            '  Session fact rows: %d | User-daily rows: %d | Dept-daily rows: %d',
            $sessionFactTotal,
            $userDailyTotal,
            $deptDailyTotal,
        ));
    }

    // -----------------------------------------------------------------------
    // Traditional courses — registrations, completions, attendance (clockings)
    // -----------------------------------------------------------------------

    private function seedTraditionalCourses(array $users, int $adminId): void
    {
        // Three instructor-led courses stored in the `courses` table
        $defs = [
            ['name' => 'Safety & Health Workshop',    'level' => 'beginner',     'duration' => 60],
            ['name' => 'Customer Service Excellence', 'level' => 'intermediate', 'duration' => 90],
            ['name' => 'Project Management Basics',   'level' => 'intermediate', 'duration' => 120],
        ];

        $courseIds = [];
        foreach ($defs as $d) {
            $existing = DB::table('courses')->where('name', $d['name'])->whereNull('deleted_at')->first();
            $courseIds[] = $existing
                ? $existing->id
                : DB::table('courses')->insertGetId([
                    'name'        => $d['name'],
                    'description' => 'Instructor-led ' . strtolower($d['name']) . ' training session.',
                    'level'       => $d['level'],
                    'duration'    => $d['duration'],
                    'status'      => 'published',
                    'privacy'     => 'public',
                    'created_by'  => $adminId,
                    'created_at'  => now()->subDays(60),
                    'updated_at'  => now()->subDays(60),
                ]);
        }

        foreach ($users as $user) {
            // Determine which courses the user is registered for
            [$regCourses, $baseStatus] = match ($user->profile) {
                'power'     => [$courseIds,                  'completed'],
                'regular'   => [array_slice($courseIds, 0, 2), null], // randomly completed or in_progress
                'struggling'=> [[$courseIds[0]],               'in_progress'],
                default     => [[$courseIds[0]],               'pending'],
            };

            foreach ($regCourses as $cId) {
                $status       = $baseStatus ?? (rand(0, 1) ? 'completed' : 'in_progress');
                $registeredAt = now()->subDays(rand(20, 30));
                $completedAt  = ($status === 'completed') ? now()->subDays(rand(2, 12)) : null;

                DB::table('course_registrations')->updateOrInsert(
                    ['user_id' => $user->id, 'course_id' => $cId],
                    [
                        'status'        => $status,
                        'registered_at' => $registeredAt,
                        'completed_at'  => $completedAt,
                        'rating'        => ($status === 'completed') ? rand(3, 5) : null,
                        'feedback'      => ($status === 'completed') ? 'Great training, very informative.' : null,
                        'created_at'    => $registeredAt,
                        'updated_at'    => now(),
                    ]
                );

                if ($status === 'completed') {
                    DB::table('course_completions')->updateOrInsert(
                        ['user_id' => $user->id, 'course_id' => $cId],
                        [
                            'completed_at' => $completedAt,
                            'rating'       => rand(3, 5),
                            'feedback'     => 'Well-structured and practical.',
                            'created_at'   => $completedAt,
                            'updated_at'   => $completedAt,
                        ]
                    );
                }
            }

            // Clocking / attendance records
            $days = match ($user->profile) {
                'power'     => [1, 2, 4, 5, 7, 8, 10, 12, 14, 15, 17, 19, 21, 22, 24, 26, 28, 29],
                'regular'   => [2, 5, 8, 11, 14, 17, 20, 23, 26, 29],
                'struggling'=> [4, 12, 20, 28],
                default     => [20],
            };

            foreach ($days as $daysAgo) {
                // Every 3rd attendance is general (not linked to a course)
                $linkedCourse = ($daysAgo % 3 !== 0) ? $courseIds[$daysAgo % count($courseIds)] : null;
                $duration = match ($user->profile) {
                    'power'     => rand(60, 120),
                    'regular'   => rand(45, 90),
                    'struggling'=> rand(20, 50),
                    default     => rand(10, 25),
                };
                $clockIn  = Carbon::now()->subDays($daysAgo)->setTime(rand(8, 9), rand(0, 30), 0);
                $clockOut = $clockIn->copy()->addMinutes($duration);

                DB::table('clockings')->insert([
                    'user_id'             => $user->id,
                    'course_id'           => $linkedCourse,
                    'clock_in'            => $clockIn,
                    'clock_out'           => $clockOut,
                    'duration_in_minutes' => $duration,
                    'rating'              => rand(3, 5),
                    'comment'             => null,
                    'created_at'          => $clockIn,
                    'updated_at'          => $clockOut,
                ]);
            }
        }
    }

    // -----------------------------------------------------------------------
    // Quiz data — quizzes, questions, attempts, answers
    // -----------------------------------------------------------------------

    private function seedQuizData(array $users): void
    {
        $courses = DB::table('courses')
            ->whereIn('name', ['Safety & Health Workshop', 'Customer Service Excellence'])
            ->whereNull('deleted_at')
            ->get()
            ->keyBy('name');

        if ($courses->isEmpty()) {
            return;
        }

        $quizDefs = [
            [
                'title'     => 'Safety Assessment',
                'course_id' => $courses->get('Safety & Health Workshop')?->id,
            ],
            [
                'title'     => 'Customer Service Knowledge Check',
                'course_id' => $courses->get('Customer Service Excellence')?->id,
            ],
        ];

        foreach ($quizDefs as $def) {
            $quiz = DB::table('quizzes')->where('title', $def['title'])->first();
            $quizId = $quiz
                ? $quiz->id
                : DB::table('quizzes')->insertGetId([
                    'course_id'            => $def['course_id'],
                    'title'                => $def['title'],
                    'description'          => 'Knowledge assessment for ' . $def['title'] . '.',
                    'max_attempts'         => 3,
                    'status'               => 'published',
                    'total_points'         => 100,
                    'pass_threshold'       => 70.00,
                    'required_to_proceed'  => 0,
                    'retry_delay_hours'    => 24,
                    'show_correct_answers' => 'after_pass',
                    'created_at'           => now()->subDays(25),
                    'updated_at'           => now()->subDays(25),
                ]);

            $this->seedQuizQuestions($quizId);

            $questions = DB::table('quiz_questions')
                ->where('quiz_id', $quizId)
                ->orderBy('order')
                ->get();

            foreach ($users as $user) {
                if ($user->profile === 'inactive') {
                    continue;
                }
                if (DB::table('quiz_attempts')->where('quiz_id', $quizId)->where('user_id', $user->id)->exists()) {
                    continue;
                }

                $this->createAttemptWithAnswers($user, $quizId, $questions, 1);

                if ($user->profile === 'struggling') {
                    $this->createAttemptWithAnswers($user, $quizId, $questions, 2);
                }
            }
        }
    }

    private function seedQuizQuestions(int $quizId): void
    {
        if (DB::table('quiz_questions')->where('quiz_id', $quizId)->exists()) {
            return;
        }

        $qs = [
            [
                'text'    => 'What should you do first when you notice a potential workplace hazard?',
                'opts'    => ['Ignore it', 'Report it immediately', 'Work around it', 'Ask a colleague'],
                'correct' => 'Report it immediately',
            ],
            [
                'text'    => 'Which personal protective equipment (PPE) is required when working with chemicals?',
                'opts'    => ['Sunglasses', 'Safety gloves and goggles', 'Regular clothing', 'Headphones'],
                'correct' => 'Safety gloves and goggles',
            ],
            [
                'text'    => 'How often must emergency exit routes be inspected?',
                'opts'    => ['Once a year', 'Quarterly', 'Monthly', 'Never'],
                'correct' => 'Monthly',
            ],
            [
                'text'    => 'What does a yellow safety sign typically indicate?',
                'opts'    => ['Danger', 'Safe to proceed', 'Caution / Warning', 'First aid station'],
                'correct' => 'Caution / Warning',
            ],
        ];

        foreach ($qs as $i => $q) {
            DB::table('quiz_questions')->insert([
                'quiz_id'       => $quizId,
                'question_text' => $q['text'],
                'type'          => 'radio',
                'points'        => 25,
                'options'       => json_encode($q['opts']),
                'correct_answer'=> json_encode($q['correct']),
                'order'         => $i + 1,
                'created_at'    => now()->subDays(25),
                'updated_at'    => now()->subDays(25),
            ]);
        }
    }

    private function createAttemptWithAnswers(object $user, int $quizId, $questions, int $attemptNum): void
    {
        $numCorrect = match ($user->profile) {
            'power'     => 4,
            'regular'   => 3,
            'struggling'=> ($attemptNum === 1) ? 1 : 2,
            default     => 0,
        };

        $score       = $numCorrect * 25;
        $passed      = $score >= 70;
        $startedAt   = Carbon::now()->subDays(rand(3, 18) + ($attemptNum - 1));
        $completedAt = $startedAt->copy()->addMinutes(rand(8, 30));

        $attemptId = DB::table('quiz_attempts')->insertGetId([
            'quiz_id'        => $quizId,
            'user_id'        => $user->id,
            'attempt_number' => $attemptNum,
            'started_at'     => $startedAt,
            'completed_at'   => $completedAt,
            'score'          => $score,
            'total_score'    => $score,
            'passed'         => $passed ? 1 : 0,
            'created_at'     => $startedAt,
            'updated_at'     => $completedAt,
        ]);

        foreach ($questions->values() as $i => $q) {
            $isCorrect = ($i < $numCorrect);
            $opts      = json_decode($q->options ?? '[]', true);
            $correct   = json_decode($q->correct_answer ?? 'null', true);
            $answer    = $isCorrect ? $correct : $this->pickWrongOption($opts, $correct);

            DB::table('quiz_answers')->insert([
                'quiz_attempt_id'  => $attemptId,
                'quiz_question_id' => $q->id,
                'answer'           => is_string($answer) ? $answer : json_encode($answer),
                'is_correct'       => $isCorrect ? 1 : 0,
                'points_earned'    => $isCorrect ? $q->points : 0,
                'created_at'       => $completedAt,
                'updated_at'       => $completedAt,
            ]);
        }
    }

    private function pickWrongOption(array $opts, $correct): string
    {
        foreach ($opts as $opt) {
            if ($opt !== $correct) {
                return $opt;
            }
        }
        return 'N/A';
    }

    // -----------------------------------------------------------------------
    // Evaluations — per-user scores driving the department performance report
    // -----------------------------------------------------------------------

    private function seedEvaluations(array $users): void
    {
        $courseIds = DB::table('courses')
            ->whereIn('name', ['Safety & Health Workshop', 'Customer Service Excellence', 'Project Management Basics'])
            ->whereNull('deleted_at')
            ->pluck('id')
            ->toArray();

        if (empty($courseIds)) {
            return;
        }

        foreach ($users as $user) {
            [$lo, $hi] = match ($user->profile) {
                'power'     => [82, 100],
                'regular'   => [62, 82],
                'struggling'=> [40, 65],
                default     => [30, 55],
            };

            foreach ($courseIds as $cId) {
                if (DB::table('evaluations')
                    ->where('user_id', $user->id)
                    ->where('course_id', $cId)
                    ->where('course_type', 'regular')
                    ->exists()) {
                    continue;
                }

                $score = rand($lo, $hi);

                DB::table('evaluations')->insert([
                    'user_id'          => $user->id,
                    'department_id'    => $user->department_id,
                    'course_type'      => 'regular',
                    'course_id'        => $cId,
                    'course_online_id' => null,
                    'total_score'      => $score,
                    'performance_level'=> \App\Enums\PerformanceLevel::getLevelByScore($score),
                    'created_at' => Carbon::now()->subDays(rand(5, 20)),
                    'updated_at' => Carbon::now()->subDays(rand(1, 4)),
                ]);
            }
        }
    }
}
