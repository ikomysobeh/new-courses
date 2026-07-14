<?php

namespace App\Services\Evaluation\Submission;

use App\Enums\PerformanceLevel;
use App\Models\CourseAssignment;
use App\Models\CourseOnlineAssignment;
use App\Models\Evaluation;
use App\Models\EvaluationHistory;
use App\Models\EvaluationType;
use App\Models\Course;
use App\Models\CourseOnline;
use App\Models\User;
use App\Support\Filtering\FilterableQuery;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class EvaluationService
{
    use FilterableQuery;

    public function createEvaluation(array $data): Evaluation
    {
        $this->verifyCourseAssignment($data);

        return DB::transaction(function () use ($data) {
            $scores = $data['scores'];
            $totalScore = $this->calcTotalScore($scores);
            $level = PerformanceLevel::getLevelByScore($totalScore);
            [$min, $max] = array_values(PerformanceLevel::getRangeByLevel($level));

            $eval = Evaluation::updateOrCreate(
                $this->uniqueKey($data),
                [
                    'department_id'          => $data['department_id'],
                    'course_type'            => $data['course_type'],
                    'course_id'              => $data['course_id'] ?? null,
                    'course_online_id'       => $data['course_online_id'] ?? null,
                    'total_score'            => $totalScore,
                    'performance_level'      => $level,
                    'performance_points_min' => $min,
                    'performance_points_max' => $max,
                ]
            );

            // Replace history rows on every upsert
            EvaluationHistory::where('evaluation_id', $eval->id)->delete();
            $this->insertHistoryRows($eval->id, $data['course_online_id'] ?? null, $scores);

            return $eval->load(['user', 'department', 'course', 'courseOnline', 'histories']);
        });
    }

    public function updateEvaluation(int $id, array $data): Evaluation
    {
        $eval = Evaluation::findOrFail($id);

        return DB::transaction(function () use ($eval, $data) {
            $scores = $data['scores'];
            $totalScore = $this->calcTotalScore($scores);
            $level = PerformanceLevel::getLevelByScore($totalScore);
            [$min, $max] = array_values(PerformanceLevel::getRangeByLevel($level));

            $eval->update([
                'total_score'            => $totalScore,
                'performance_level'      => $level,
                'performance_points_min' => $min,
                'performance_points_max' => $max,
            ]);

            EvaluationHistory::where('evaluation_id', $eval->id)->delete();
            $this->insertHistoryRows($eval->id, $eval->course_online_id, $scores);

            return $eval->load(['user', 'department', 'course', 'courseOnline', 'histories']);
        });
    }

    public function deleteEvaluation(int $id): void
    {
        Evaluation::findOrFail($id)->delete();
    }

    public function bulkCreateEvaluations(array $evaluations): array
    {
        $created = 0;
        $updated = 0;
        $failed  = 0;
        $errors  = [];

        foreach ($evaluations as $index => $item) {
            try {
                $wasNew = !Evaluation::where($this->uniqueKey($item))->exists();
                $this->createEvaluation($item);
                $wasNew ? $created++ : $updated++;
            } catch (\Throwable $e) {
                $failed++;
                $errors[$index] = $e->getMessage();
            }
        }

        return compact('created', 'updated', 'failed', 'errors');
    }

    public function getAllForAdmin(array $params): LengthAwarePaginator
    {
        $query = Evaluation::with(['user', 'department', 'course', 'courseOnline', 'histories']);

        // Legacy explicit date range (start_date / end_date on created_at)
        if (!empty($params['start_date'])) {
            $query->whereDate('created_at', '>=', $params['start_date']);
        }
        if (!empty($params['end_date'])) {
            $query->whereDate('created_at', '<=', $params['end_date']);
        }

        return $this->applyFilters($query, $params, [
            'searchable'  => ['user.name', 'user.email'],
            'filters'     => [
                'course_type'       => 'exact',
                'department_id'     => 'exact',
                'user_id'           => 'exact',
                'performance_level' => 'exact',
            ],
            'dateColumn'  => 'created_at',
            'sortable'    => ['created_at', 'total_score', 'performance_level'],
            'defaultSort' => ['created_at', 'desc'],
            'perPage'     => 20,
        ]);
    }

    public function getAllForUser(int $userId): LengthAwarePaginator
    {
        return Evaluation::with(['course', 'courseOnline', 'histories'])
            ->where('user_id', $userId)
            ->latest()
            ->paginate(20);
    }

    public function getById(int $id): Evaluation
    {
        return Evaluation::with(['user', 'department', 'course', 'courseOnline', 'histories'])->findOrFail($id);
    }

    public function getUsersWithCoursesByDepartment(int $deptId, string $courseType): Collection
    {
        return User::where('department_id', $deptId)
            ->with($courseType === 'online' ? 'courseOnlineAssignments.courseOnline' : 'courseAssignments.course')
            ->get();
    }

    public function getUserCourses(int $userId, string $courseType): Collection
    {
        if ($courseType === 'online') {
            return CourseOnline::whereHas('assignments', fn($q) => $q->where('user_id', $userId))->get();
        }
        return Course::whereHas('assignments', fn($q) => $q->where('user_id', $userId))->get();
    }

    // ---- Private helpers ----

    private function verifyCourseAssignment(array $data): void
    {
        if ($data['course_type'] === 'regular') {
            $assigned = CourseAssignment::where('user_id', $data['user_id'])
                ->where('course_id', $data['course_id'] ?? null)
                ->exists();
            if (!$assigned) {
                throw ValidationException::withMessages([
                    'course_id' => ['This user is not assigned to the specified course.'],
                ]);
            }
        } else {
            $assigned = CourseOnlineAssignment::where('user_id', $data['user_id'])
                ->where('course_online_id', $data['course_online_id'] ?? null)
                ->exists();
            if (!$assigned) {
                throw ValidationException::withMessages([
                    'course_online_id' => ['This user is not assigned to the specified online course.'],
                ]);
            }
        }
    }

    private function uniqueKey(array $data): array
    {
        return [
            'user_id'          => $data['user_id'],
            'course_type'      => $data['course_type'],
            'course_id'        => $data['course_id'] ?? null,
            'course_online_id' => $data['course_online_id'] ?? null,
        ];
    }

    private function calcTotalScore(array $scores): int
    {
        return (int) array_sum(array_column($scores, 'score_given'));
    }

    private function insertHistoryRows(int $evalId, ?int $courseOnlineId, array $scores): void
    {
        foreach ($scores as $row) {
            $type = EvaluationType::with('config')->findOrFail($row['evaluation_type_id']);

            EvaluationHistory::create([
                'evaluation_id'   => $evalId,
                'course_online_id' => $courseOnlineId,
                'config_name'     => $type->config->name,   // snapshot
                'type_name'       => $type->type_name,       // snapshot
                'score_given'     => $row['score_given'],
                'max_score'       => $type->score_value,
            ]);
        }
    }
}
