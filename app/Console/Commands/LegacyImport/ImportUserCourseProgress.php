<?php

namespace App\Console\Commands\LegacyImport;

use App\Models\CourseModule;
use App\Models\CourseOnline;
use App\Models\User;
use App\Models\UserCourseProgress;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * user_course_progress has no old-schema equivalent - it's derived from
 * course_online_assignments (which used to carry status/progress_percentage/
 * current_module_id directly, before those columns were dropped in favor of
 * this table) plus aggregate counts computed straight from the legacy
 * database. Doesn't extend LegacyImportCommand: it upserts by
 * (user_id, course_online_id) - the table's real unique key - rather than by
 * legacy_id, since this is a derived "current state" row, not a 1:1 copy of
 * one old row.
 */
class ImportUserCourseProgress extends Command
{
    protected $signature = 'legacy:import-user-course-progress';

    protected $description = "Derive user_course_progress from legacy course_online_assignments + aggregate counts (total/completed content items) computed directly from legacy module_content and user_content_progress. status remapped: assigned->not_started, in_progress->in_progress, completed->completed. last_accessed_at derived as the user's latest legacy user_content_progress row for that course. last_session_id left null (learning_sessions not imported yet).";

    protected array $userMap = [];

    protected array $courseOnlineMap = [];

    protected array $moduleMap = [];

    public function handle(): int
    {
        $this->userMap = User::query()->whereNotNull('legacy_id')->pluck('id', 'legacy_id')->all();
        $this->courseOnlineMap = CourseOnline::query()->whereNotNull('legacy_id')->pluck('id', 'legacy_id')->all();
        $this->moduleMap = CourseModule::query()->whereNotNull('legacy_id')->pluck('id', 'legacy_id')->all();

        $totalContentByCourseOnline = DB::connection('legacy')
            ->table('module_content')
            ->join('course_modules', 'course_modules.id', '=', 'module_content.module_id')
            ->selectRaw('course_modules.course_online_id, COUNT(module_content.id) as total')
            ->groupBy('course_modules.course_online_id')
            ->pluck('total', 'course_online_id');

        $completedContentByUserCourse = DB::connection('legacy')
            ->table('user_content_progress')
            ->where('is_completed', 1)
            ->selectRaw('user_id, course_online_id, COUNT(*) as total')
            ->groupBy('user_id', 'course_online_id')
            ->get()
            ->keyBy(fn ($row) => $row->user_id.'-'.$row->course_online_id);

        $lastAccessedByUserCourse = DB::connection('legacy')
            ->table('user_content_progress')
            ->selectRaw('user_id, course_online_id, MAX(last_accessed_at) as last_accessed')
            ->groupBy('user_id', 'course_online_id')
            ->get()
            ->keyBy(fn ($row) => $row->user_id.'-'.$row->course_online_id);

        $legacyAssignments = DB::connection('legacy')->table('course_online_assignments')->get();

        $this->info("Deriving user_course_progress from {$legacyAssignments->count()} legacy.course_online_assignments rows");

        $statusMap = ['assigned' => 'not_started', 'in_progress' => 'in_progress', 'completed' => 'completed'];
        $bar = $this->output->createProgressBar($legacyAssignments->count());
        $skipped = [];

        foreach ($legacyAssignments as $old) {
            $newUserId = $this->userMap[$old->user_id] ?? null;
            $newCourseOnlineId = $this->courseOnlineMap[$old->course_online_id] ?? null;

            if ($newUserId === null || $newCourseOnlineId === null) {
                $skipped[] = $old->id;
                $bar->advance();

                continue;
            }

            $newCurrentModuleId = $old->current_module_id !== null
                ? ($this->moduleMap[$old->current_module_id] ?? null)
                : null;

            $key = $old->user_id.'-'.$old->course_online_id;

            UserCourseProgress::updateOrCreate(
                ['user_id' => $newUserId, 'course_online_id' => $newCourseOnlineId],
                [
                    'legacy_id' => $old->id,
                    'progress_percentage' => $old->progress_percentage,
                    'status' => $statusMap[$old->status] ?? 'not_started',
                    'total_content_items' => $totalContentByCourseOnline[$old->course_online_id] ?? 0,
                    'completed_content_items' => $completedContentByUserCourse[$key]->total ?? 0,
                    'current_module_id' => $newCurrentModuleId,
                    'started_at' => $old->started_at,
                    'completed_at' => $old->completed_at,
                    'last_accessed_at' => $lastAccessedByUserCourse[$key]->last_accessed ?? null,
                    'last_session_id' => null,
                ]
            );

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();

        if ($skipped !== []) {
            $this->warn(sprintf('Skipped %d row(s) with unresolved mappings: %s', count($skipped), implode(', ', $skipped)));
        }

        $newCount = UserCourseProgress::count();
        $this->info('--- Verification ---');
        $this->line("Legacy assignment rows: {$legacyAssignments->count()}");
        $this->line("user_course_progress rows now: {$newCount}");

        foreach ($legacyAssignments->random(min(3, $legacyAssignments->count())) as $old) {
            $newUserId = $this->userMap[$old->user_id] ?? null;
            $newCourseOnlineId = $this->courseOnlineMap[$old->course_online_id] ?? null;
            $new = $newUserId && $newCourseOnlineId
                ? UserCourseProgress::where('user_id', $newUserId)->where('course_online_id', $newCourseOnlineId)->first()
                : null;
            $this->line("legacy assignment id={$old->id} (user_id={$old->user_id}, course_online_id={$old->course_online_id})");
            $this->line('  OLD: '.json_encode($old));
            $this->line('  NEW: '.($new ? json_encode($new->toArray()) : 'MISSING'));
        }

        return self::SUCCESS;
    }
}
