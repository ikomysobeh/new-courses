<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class FeedbackSeeder extends Seeder
{
    public function run(): void
    {
        $admin = DB::table('users')->where('role', 'admin')->first();
        $users = DB::table('users')->where('role', 'user')->get();

        if (! $admin || $users->isEmpty()) {
            $this->command?->warn('FeedbackSeeder: missing admin or users — skipping.');
            return;
        }

        // Employee feedback
        $feedbackItems = [
            ['type' => 'suggestion',      'status' => 'under_review', 'title' => 'Add Dark Mode to the Portal',
             'description' => 'A dark mode option would reduce eye strain during late-night or extended training sessions. Many modern platforms support this and it would be a welcome addition.',
             'admin_response' => null],

            ['type' => 'improvement',     'status' => 'approved',     'title' => 'Course Search Needs Better Filters',
             'description' => 'The current search for courses is too basic. Adding filters by level, duration, department, and completion status would make finding the right course much faster.',
             'admin_response' => 'Great point — we are adding advanced filters in the next release. Thank you for the detailed feedback.'],

            ['type' => 'feature_request', 'status' => 'pending',      'title' => 'Mobile App for Learning on the Go',
             'description' => 'Many employees work remotely or travel frequently. A dedicated mobile app would allow them to watch course videos and take quizzes without needing a laptop.',
             'admin_response' => null],

            ['type' => 'general',         'status' => 'approved',     'title' => 'Excellent New Learning Platform',
             'description' => 'The new LMS is a huge improvement over the old system. The video quality is excellent, quizzes are well-structured, and the progress tracking is very helpful.',
             'admin_response' => 'Thank you for the kind words — we will share this with the development team!'],

            ['type' => 'improvement',     'status' => 'pending',      'title' => 'Quiz Timer Should Be More Visible',
             'description' => 'During quiz attempts the countdown timer is hard to notice at the top of the page. A more prominent display or an audio cue when time is running low would help.',
             'admin_response' => null],

            ['type' => 'suggestion',      'status' => 'rejected',     'title' => 'Add Gamification Badges and Points',
             'description' => 'Adding badges, digital certificates, and a leaderboard would make training more engaging and motivate employees to complete courses faster.',
             'admin_response' => 'Interesting idea — however gamification is not aligned with our current compliance-focused training goals. We will revisit this in the future.'],
        ];

        $feedbackStatuses = array_column($feedbackItems, 'status');
        $feedbackCreated  = 0;

        foreach ($feedbackItems as $idx => $fb) {
            $user = $users->values()->get($idx % $users->count());

            DB::table('employee_feedback')->insert([
                'user_id'        => $user->id,
                'type'           => $fb['type'],
                'title'          => $fb['title'],
                'description'    => $fb['description'],
                'status'         => $fb['status'],
                'admin_response' => $fb['admin_response'],
                'created_at'     => now()->subDays(rand(5, 30)),
                'updated_at'     => now()->subDays(rand(1, 4)),
            ]);
            $feedbackCreated++;
        }

        // Bug reports
        $bugs = [
            [
                'priority' => 'high',
                'status'   => 'resolved',
                'title'    => 'Video Player Freezes on Safari',
                'description'    => 'The video player intermittently freezes on Safari (macOS). Refreshing the page resolves the issue temporarily but it recurs after a few minutes.',
                'steps'          => "1. Open any course video on Safari macOS\n2. Allow it to play for ~3 minutes\n3. Player freezes — audio continues but video is stuck\n4. Hard refresh (Cmd+Shift+R) resolves it temporarily",
                'page_url'       => '/user/courses/1',
                'resolved_days'  => 5,
            ],
            [
                'priority' => 'critical',
                'status'   => 'resolved',
                'title'    => 'Quiz Submit Button Stays Disabled After Answering All Questions',
                'description'    => 'After completing all questions in a quiz the Submit button remains greyed out and unclickable. Affects all browsers. Users cannot finish the quiz.',
                'steps'          => "1. Navigate to any quiz\n2. Answer every question\n3. Submit button remains disabled\n4. Cannot proceed or submit the quiz",
                'page_url'       => '/user/quiz/2',
                'resolved_days'  => 2,
            ],
            [
                'priority' => 'medium',
                'status'   => 'in_progress',
                'title'    => 'Course Progress Shows 99% After Watching All Content',
                'description'    => 'After watching all video content in a course the progress indicator shows 99% instead of 100% and the course is not marked as completed.',
                'steps'          => "1. Enroll in any online course\n2. Watch all videos to the end\n3. Check course card — shows 99%, status stays in_progress",
                'page_url'       => '/user/courses',
                'resolved_days'  => null,
            ],
            [
                'priority' => 'low',
                'status'   => 'open',
                'title'    => 'Profile Picture Upload Very Slow',
                'description'    => 'Uploading a profile picture takes more than 30 seconds regardless of file size. Other users on fast connections report the same slowness.',
                'steps'          => "1. Go to profile settings\n2. Click Change Photo\n3. Select any image (tested with <500 KB JPEG)\n4. Progress spinner runs for 30+ seconds",
                'page_url'       => '/profile',
                'resolved_days'  => null,
            ],
        ];

        $bugCreated = 0;

        foreach ($bugs as $idx => $bug) {
            $reporter   = $users->values()->get($idx % $users->count());
            $resolvedAt = $bug['resolved_days'] !== null
                ? now()->subDays($bug['resolved_days'])
                : null;

            DB::table('bug_reports')->insert([
                'reported_by'        => $reporter->id,
                'assigned_to'        => $admin->id,
                'priority'           => $bug['priority'],
                'status'             => $bug['status'],
                'title'              => $bug['title'],
                'description'        => $bug['description'],
                'steps_to_reproduce' => $bug['steps'],
                'page_url'           => $bug['page_url'],
                'resolved_at'        => $resolvedAt,
                'created_at'         => now()->subDays(rand(3, 25)),
                'updated_at'         => now()->subDays(rand(1, 3)),
            ]);
            $bugCreated++;
        }

        $this->command?->info("FeedbackSeeder: {$feedbackCreated} feedback records, {$bugCreated} bug reports.");
    }
}
