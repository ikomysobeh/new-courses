<?php

namespace App\Console\Commands\LegacyImport;

use App\Enums\PerformanceLevel;
use App\Models\Course;
use App\Models\CourseOnline;
use App\Models\Evaluation;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ImportEvaluations extends LegacyImportCommand
{
    protected $signature = 'legacy:import-evaluations';

    protected $description = "Import evaluations. total_score is rescaled from the old raw-points scale (max is the sum of applicable evaluation_configs.max_score, 15 for both course types currently) to 0-100, since that's what the new PerformanceLevel enum expects. performance_level and performance_points_min/max are recomputed from the rescaled score via App\Enums\PerformanceLevel (client decision - 11 boundary evaluations shift one level vs. what the old system originally assigned). incentive_amount is dropped (not in new schema - handled by the separate incentives rules table in Phase 7, not stored per-evaluation).";

    protected array $userMap = [];

    protected array $courseMap = [];

    protected array $courseOnlineMap = [];

    protected array $maxScoreByCourseType = [];

    protected function legacyTable(): string
    {
        return 'evaluations';
    }

    protected function newModel(): string
    {
        return Evaluation::class;
    }

    protected function beforeImport(): void
    {
        $this->userMap = User::query()->whereNotNull('legacy_id')->pluck('id', 'legacy_id')->all();
        $this->courseMap = Course::query()->whereNotNull('legacy_id')->pluck('id', 'legacy_id')->all();
        $this->courseOnlineMap = CourseOnline::query()->whereNotNull('legacy_id')->pluck('id', 'legacy_id')->all();

        foreach (['regular', 'online'] as $courseType) {
            $this->maxScoreByCourseType[$courseType] = DB::connection('legacy')
                ->table('evaluation_configs')
                ->whereIn('applies_to', [$courseType, 'both'])
                ->sum('max_score');
        }
    }

    protected function mapRow(array $old): ?array
    {
        $newUserId = $this->userMap[$old['user_id']] ?? null;

        if ($newUserId === null) {
            $this->error("No imported User for legacy user_id={$old['user_id']} (evaluation legacy_id={$old['id']})");

            return null;
        }

        $newCourseId = null;
        $newCourseOnlineId = null;

        if ($old['course_type'] === 'regular' && $old['course_id'] !== null) {
            $newCourseId = $this->courseMap[$old['course_id']] ?? null;

            if ($newCourseId === null) {
                $this->error("No imported Course for legacy course_id={$old['course_id']} (evaluation legacy_id={$old['id']})");

                return null;
            }
        }

        if ($old['course_type'] === 'online' && $old['course_online_id'] !== null) {
            $newCourseOnlineId = $this->courseOnlineMap[$old['course_online_id']] ?? null;

            if ($newCourseOnlineId === null) {
                $this->error("No imported CourseOnline for legacy course_online_id={$old['course_online_id']} (evaluation legacy_id={$old['id']})");

                return null;
            }
        }

        $maxScore = $this->maxScoreByCourseType[$old['course_type']] ?? 15;
        $rescaledScore = $maxScore > 0 ? (int) round($old['total_score'] / $maxScore * 100) : 0;

        $performanceLevel = null;
        $pointsMin = null;
        $pointsMax = null;

        if ($old['performance_level'] !== null) {
            $performanceLevel = PerformanceLevel::getLevelByScore($rescaledScore);
            [$pointsMin, $pointsMax] = PerformanceLevel::getRangeByLevel($performanceLevel);
        }

        return [
            'legacy_id' => $old['id'],
            'user_id' => $newUserId,
            'department_id' => $old['department_id'],
            'course_type' => $old['course_type'],
            'course_id' => $newCourseId,
            'course_online_id' => $newCourseOnlineId,
            'total_score' => $rescaledScore,
            'performance_level' => $performanceLevel,
            'performance_points_min' => $pointsMin,
            'performance_points_max' => $pointsMax,
            'created_at' => $old['created_at'],
            'updated_at' => $old['updated_at'],
        ];
    }
}
