<?php

namespace App\Console\Commands\LegacyImport;

use App\Models\CourseModule;
use App\Models\CourseOnline;
use App\Models\ModuleContent;
use App\Models\User;
use App\Models\UserContentProgress;

class ImportUserContentProgress extends LegacyImportCommand
{
    protected $signature = 'legacy:import-user-content-progress';

    protected $description = 'Import user_content_progress. Drops total_duration/video_id/task_completed (not in new schema - video_id is redundant since content_id already points at the module_contents row, which itself has video_id). Remaps content_id via module_contents, module_id via course_modules, course_online_id via course_onlines.';

    protected array $userMap = [];

    protected array $contentMap = [];

    protected array $moduleMap = [];

    protected array $courseOnlineMap = [];

    protected function legacyTable(): string
    {
        return 'user_content_progress';
    }

    protected function newModel(): string
    {
        return UserContentProgress::class;
    }

    protected function beforeImport(): void
    {
        $this->userMap = User::query()->whereNotNull('legacy_id')->pluck('id', 'legacy_id')->all();
        $this->contentMap = ModuleContent::query()->whereNotNull('legacy_id')->pluck('id', 'legacy_id')->all();
        $this->moduleMap = CourseModule::query()->whereNotNull('legacy_id')->pluck('id', 'legacy_id')->all();
        $this->courseOnlineMap = CourseOnline::query()->whereNotNull('legacy_id')->pluck('id', 'legacy_id')->all();
    }

    protected function mapRow(array $old): ?array
    {
        $newUserId = $this->userMap[$old['user_id']] ?? null;
        $newContentId = $this->contentMap[$old['content_id']] ?? null;
        $newModuleId = $this->moduleMap[$old['module_id']] ?? null;
        $newCourseOnlineId = $this->courseOnlineMap[$old['course_online_id']] ?? null;

        if ($newUserId === null || $newContentId === null || $newModuleId === null || $newCourseOnlineId === null) {
            $this->error("Unresolved mapping for user_content_progress legacy_id={$old['id']} (user_id={$old['user_id']}, content_id={$old['content_id']}, module_id={$old['module_id']}, course_online_id={$old['course_online_id']})");

            return null;
        }

        return [
            'legacy_id' => $old['id'],
            'user_id' => $newUserId,
            'content_id' => $newContentId,
            'course_online_id' => $newCourseOnlineId,
            'module_id' => $newModuleId,
            'content_type' => $old['content_type'],
            'watch_time' => $old['watch_time'],
            'pdf_pages_viewed' => $old['pdf_pages_viewed'],
            'completion_percentage' => $old['completion_percentage'],
            'is_completed' => $old['is_completed'],
            'completed_at' => $old['completed_at'],
            'last_accessed_at' => $old['last_accessed_at'],
            'playback_position' => $old['playback_position'],
            'created_at' => $old['created_at'],
            'updated_at' => $old['updated_at'],
        ];
    }
}
