<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            // Core reference data (no user dependencies)
            UserLevelSeeder::class,
            UserLevelTierSeeder::class,
            DepartmentSeeder::class,
            AdminSeeder::class,
            UserSeeder::class,

            // Category / config reference data
            AudioCategorySeeder::class,
            VideoCategorySeeder::class,
            EvaluationConfigSeeder::class,

            // Content (depends on admin user + categories above)
            AudioSeeder::class,
            VideoSeeder::class,
            CourseSeeder::class,

            // Content enhancement
            VideoQualitySeeder::class,      // video_qualities — depends on VideoSeeder

            // Dependent content
            QuizSeeder::class,              // depends on CourseSeeder
            CourseOnlineSeeder::class,      // depends on VideoSeeder

            // Module PDF content (must run after CourseOnlineSeeder creates modules)
            ModuleContentPdfSeeder::class,  // module_contents (pdf) + module_content_pdfs

            // Interactions — depend on content + users
            AudioInteractionSeeder::class,  // audio_assignments + audio_progress
            CourseAssignmentSeeder::class,  // course_assignments + quiz_assignments

            // Blog / podcast
            PodcastPostSeeder::class,
            PostInteractionSeeder::class,   // post_comments + post_likes

            // User activity & feedback
            FeedbackSeeder::class,          // employee_feedback + bug_reports
            ActivityLogSeeder::class,       // activity_logs

            // Reporting — must run after courses + users are fully seeded
            ReportingSeeder::class,

            // Post-reporting (depend on data created by ReportingSeeder)
            CourseAnalyticsSeeder::class,      // course_analytics
            EvaluationHistorySeeder::class,    // evaluation_histories
            NotificationSeeder::class,         // notification_sends + user_notifications
            UserContentProgressSeeder::class,  // user_content_progress
        ]);
    }
}
