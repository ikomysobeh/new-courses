<?php

namespace App\Console\Commands\LegacyImport;

use App\Models\CourseOnline;
use App\Models\LearningSession;
use App\Models\ModuleContent;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ImportLearningSessions extends LegacyImportCommand
{
    protected $signature = 'legacy:import-learning-sessions';

    protected $description = "Import learning_sessions. Per client decision, attention_score/is_suspicious are copied as-is from the old system's own computed values, not recomputed with the new formula. content_type is derived (old had no such column on the session itself) via the legacy module_content row. wall_clock_seconds is computed from session_start/session_end. Drops total_duration_minutes/video_watch_time/is_within_allowed_time/video_total_duration/clicks_count/api_key_id/cheating_score (not in new schema).";

    protected array $userMap = [];

    protected array $courseOnlineMap = [];

    protected array $contentMap = [];

    protected array $legacyContentTypes = [];

    protected function legacyTable(): string
    {
        return 'learning_sessions';
    }

    protected function newModel(): string
    {
        return LearningSession::class;
    }

    protected function beforeImport(): void
    {
        $this->userMap = User::query()->whereNotNull('legacy_id')->pluck('id', 'legacy_id')->all();
        $this->courseOnlineMap = CourseOnline::query()->whereNotNull('legacy_id')->pluck('id', 'legacy_id')->all();
        $this->contentMap = ModuleContent::query()->whereNotNull('legacy_id')->pluck('id', 'legacy_id')->all();
        $this->legacyContentTypes = DB::connection('legacy')->table('module_content')->pluck('content_type', 'id')->all();
    }

    protected function mapRow(array $old): ?array
    {
        $newUserId = $this->userMap[$old['user_id']] ?? null;
        $newCourseOnlineId = $this->courseOnlineMap[$old['course_online_id']] ?? null;
        $newContentId = $old['content_id'] !== null ? ($this->contentMap[$old['content_id']] ?? null) : null;
        $contentType = $old['content_id'] !== null ? ($this->legacyContentTypes[$old['content_id']] ?? null) : null;

        if ($newUserId === null || $newCourseOnlineId === null || $newContentId === null || $contentType === null) {
            $this->error("Unresolved mapping for learning_session legacy_id={$old['id']} (user_id={$old['user_id']}, course_online_id={$old['course_online_id']}, content_id={$old['content_id']})");

            return null;
        }

        $wallClockSeconds = $old['session_end'] !== null
            ? max(0, strtotime($old['session_end']) - strtotime($old['session_start']))
            : null;

        return [
            'legacy_id' => $old['id'],
            'user_id' => $newUserId,
            'course_online_id' => $newCourseOnlineId,
            'content_id' => $newContentId,
            'content_type' => $contentType,
            'session_start' => $old['session_start'],
            'session_end' => $old['session_end'],
            'last_progress_at' => $old['last_heartbeat'],
            'active_playback_time' => $old['active_playback_time'],
            'wall_clock_seconds' => $wallClockSeconds,
            'skip_count' => $old['video_skip_count'],
            'seek_count' => $old['seek_count'],
            'replay_count' => $old['video_replay_count'],
            'pause_count' => $old['pause_count'],
            'speed_changes' => $old['speed_changes'],
            'fullscreen_count' => $old['fullscreen_count'],
            'video_completion_percentage' => $old['video_completion_percentage'],
            'attention_score' => $old['attention_score'],
            'is_suspicious' => $old['is_suspicious_activity'],
            'events_log' => $old['video_events'] !== null && $old['video_events'] !== '' ? json_decode($old['video_events'], true) : null,
            'created_at' => $old['created_at'],
            'updated_at' => $old['updated_at'],
        ];
    }
}
