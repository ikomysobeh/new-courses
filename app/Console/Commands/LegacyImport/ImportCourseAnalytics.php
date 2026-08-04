<?php

namespace App\Console\Commands\LegacyImport;

use App\Models\CourseAnalytics;
use App\Models\CourseOnline;

class ImportCourseAnalytics extends LegacyImportCommand
{
    protected $signature = 'legacy:import-course-analytics';

    protected $description = "Import course_analytics. This is a computed/cached snapshot (not user-entered data), same as learning_sessions.attention_score - preserved as-is rather than recomputed, consistent with that earlier decision. Drops average_completion_time_hours/total_learning_hours/most_skipped_content_id/most_replayed_content_id/average_task_score (not in new schema).";

    protected array $courseOnlineMap = [];

    protected function legacyTable(): string
    {
        return 'course_analytics';
    }

    protected function newModel(): string
    {
        return CourseAnalytics::class;
    }

    protected function beforeImport(): void
    {
        $this->courseOnlineMap = CourseOnline::query()->whereNotNull('legacy_id')->pluck('id', 'legacy_id')->all();
    }

    protected function mapRow(array $old): ?array
    {
        $newCourseOnlineId = $this->courseOnlineMap[$old['course_online_id']] ?? null;

        if ($newCourseOnlineId === null) {
            $this->error("No imported CourseOnline for legacy course_online_id={$old['course_online_id']} (course_analytics legacy_id={$old['id']})");

            return null;
        }

        return [
            'legacy_id' => $old['id'],
            'course_online_id' => $newCourseOnlineId,
            'total_enrollments' => $old['total_enrollments'],
            'active_learners' => $old['active_learners'],
            'completed_learners' => $old['completed_learners'],
            'completion_rate' => $old['completion_rate'],
            'dropout_rate' => $old['dropout_rate'],
            'average_session_duration_minutes' => $old['average_session_duration_minutes'],
            'average_video_completion_rate' => $old['average_video_completion_rate'],
            'cheating_incidents_count' => $old['cheating_incidents_count'],
            'last_calculated_at' => $old['last_calculated_at'],
            'created_at' => $old['created_at'],
            'updated_at' => $old['updated_at'],
        ];
    }
}
