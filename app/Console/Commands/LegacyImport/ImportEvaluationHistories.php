<?php

namespace App\Console\Commands\LegacyImport;

use App\Models\CourseOnline;
use App\Models\Evaluation;
use App\Models\EvaluationConfig;
use App\Models\EvaluationHistory;

class ImportEvaluationHistories extends LegacyImportCommand
{
    protected $signature = 'legacy:import-evaluation-histories';

    protected $description = "Import evaluation_histories. category_name renamed to config_name, score renamed to score_given (unchanged - each row stays on its own category's raw point scale, unlike evaluations.total_score which is rescaled to 0-100). New max_score derived by matching config_name to evaluation_configs.name (verified exact match for all 10 configs). Drops course_type (redundant - already implied by the parent evaluation's course_type).";

    protected array $evaluationMap = [];

    protected array $courseOnlineMap = [];

    protected array $maxScoreByConfigName = [];

    protected function legacyTable(): string
    {
        return 'evaluation_histories';
    }

    protected function newModel(): string
    {
        return EvaluationHistory::class;
    }

    protected function beforeImport(): void
    {
        $this->evaluationMap = Evaluation::query()->whereNotNull('legacy_id')->pluck('id', 'legacy_id')->all();
        $this->courseOnlineMap = CourseOnline::query()->whereNotNull('legacy_id')->pluck('id', 'legacy_id')->all();
        $this->maxScoreByConfigName = EvaluationConfig::query()->pluck('max_score', 'name')->all();
    }

    protected function mapRow(array $old): ?array
    {
        $newEvaluationId = $this->evaluationMap[$old['evaluation_id']] ?? null;

        if ($newEvaluationId === null) {
            $this->error("No imported Evaluation for legacy evaluation_id={$old['evaluation_id']} (history legacy_id={$old['id']})");

            return null;
        }

        $newCourseOnlineId = null;

        if ($old['course_online_id'] !== null) {
            $newCourseOnlineId = $this->courseOnlineMap[$old['course_online_id']] ?? null;

            if ($newCourseOnlineId === null) {
                $this->error("No imported CourseOnline for legacy course_online_id={$old['course_online_id']} (history legacy_id={$old['id']})");

                return null;
            }
        }

        $maxScore = $this->maxScoreByConfigName[$old['category_name']] ?? null;

        if ($maxScore === null) {
            $this->error("No EvaluationConfig named '{$old['category_name']}' (history legacy_id={$old['id']})");

            return null;
        }

        return [
            'legacy_id' => $old['id'],
            'evaluation_id' => $newEvaluationId,
            'course_online_id' => $newCourseOnlineId,
            'config_name' => $old['category_name'],
            'type_name' => $old['type_name'],
            'score_given' => $old['score'],
            'max_score' => $maxScore,
            'created_at' => $old['created_at'],
            'updated_at' => $old['updated_at'],
        ];
    }
}
