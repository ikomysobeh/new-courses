<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UserContentProgressSeeder extends Seeder
{
    public function run(): void
    {
        $assignments = DB::table('course_online_assignments')->get();

        if ($assignments->isEmpty()) {
            $this->command?->warn('UserContentProgressSeeder: no course assignments — run ReportingSeeder first.');
            return;
        }

        $rows = 0;

        // Group by course so we only fetch modules/contents once per course
        $byCourse = $assignments->groupBy('course_online_id');

        foreach ($byCourse as $courseId => $courseAssignments) {
            $modules = DB::table('course_modules')
                ->where('course_online_id', $courseId)
                ->orderBy('order_number')
                ->get();

            if ($modules->isEmpty()) {
                continue;
            }

            // Pre-load all content for this course's modules
            $moduleIds    = $modules->pluck('id')->toArray();
            $allContents  = DB::table('module_contents')
                ->whereIn('module_id', $moduleIds)
                ->orderBy('module_id')
                ->orderBy('order_number')
                ->get()
                ->groupBy('module_id');

            $moduleCount = $modules->count();

            foreach ($courseAssignments as $assignment) {
                $userId = $assignment->user_id;

                $courseProgress = DB::table('user_course_progress')
                    ->where('user_id', $userId)
                    ->where('course_online_id', $courseId)
                    ->first();

                $overallPct = $courseProgress ? (float) $courseProgress->progress_percentage : 0.0;

                foreach ($modules->values() as $moduleIdx => $module) {
                    $contents = $allContents->get($module->id);

                    if (! $contents || $contents->isEmpty()) {
                        continue;
                    }

                    // Estimate per-module completion based on linear interpolation
                    $completedModules = (int) floor($moduleCount * $overallPct / 100);
                    $modulePct = match (true) {
                        $moduleIdx < $completedModules              => 100.0,
                        $moduleIdx === $completedModules && $overallPct < 100 => fmod($overallPct * $moduleCount, 100),
                        default                                     => 0.0,
                    };
                    $modulePct = max(0.0, min(100.0, $modulePct));

                    if ($modulePct <= 0.0) {
                        continue;
                    }

                    foreach ($contents as $content) {
                        $exists = DB::table('user_content_progress')
                            ->where('user_id', $userId)
                            ->where('content_id', $content->id)
                            ->exists();

                        if ($exists) {
                            continue;
                        }

                        $pct       = min(100.0, max(0.0, $modulePct + rand(-4, 4)));
                        $completed = $pct >= 95.0;
                        $duration  = max(1, (int) ($content->duration ?? 600));
                        $watchTime = (int) ($duration * $pct / 100);

                        DB::table('user_content_progress')->insert([
                            'user_id'               => $userId,
                            'content_id'            => $content->id,
                            'course_online_id'      => $courseId,
                            'module_id'             => $module->id,
                            'content_type'          => $content->content_type,
                            'watch_time'            => $content->content_type === 'video' ? $watchTime : 0,
                            'pdf_pages_viewed'      => $content->content_type === 'pdf'   ? rand(1, 15) : 0,
                            'completion_percentage' => round($pct, 2),
                            'is_completed'          => $completed ? 1 : 0,
                            'completed_at'          => $completed ? now()->subDays(rand(1, 10)) : null,
                            'last_accessed_at'      => now()->subDays(rand(0, 7)),
                            'playback_position'     => $content->content_type === 'video'
                                ? round($watchTime / $duration, 4)
                                : 0,
                            'created_at'            => now()->subDays(rand(10, 30)),
                            'updated_at'            => now()->subDays(rand(0, 5)),
                        ]);
                        $rows++;
                    }
                }
            }
        }

        $this->command?->info("UserContentProgressSeeder: {$rows} content progress records.");
    }
}
