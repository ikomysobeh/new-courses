<?php

namespace App\Services\Reporting\Progress;

use App\Services\AttentionScore\AttentionScoreConfigService;
use Illuminate\Support\Facades\DB;

/**
 * Computes the weighted "learning score" used by the User Course Progress report.
 *
 * Adapted from the old nvt-courses project to the new schema:
 *  - attention_score is read straight from learning_sessions (stored column)
 *  - quiz score comes from quiz_attempts + quizzes (no module_quiz_results table)
 *
 * Traditional courses: (completion x 0.3333) + (progress x 0.3333) + (quiz x 0.3334)
 * Online courses:      config-driven blended weights (default: completion/progress/
 *                      attention/quiz x 0.25 each) - suspicious_penalty
 */
class LearningScoreCalculator
{
    public function __construct(private readonly AttentionScoreConfigService $attentionScoreConfigService) {}

    public function calculate(
        float $completionRate,
        float $progressPercentage,
        float $attentionScore,
        float $quizScore,
        int $suspiciousActivities,
        int $totalSessions,
        string $courseType = 'online'
    ): float {
        if ($courseType === 'traditional') {
            $finalScore = ($completionRate * 0.3333)
                + ($progressPercentage * 0.3333)
                + ($quizScore * 0.3334);
        } else {
            $weights = $this->attentionScoreConfigService->getActiveConfig()->config['blended_score_weights'];

            $suspiciousPenalty = 0.0;
            if ($totalSessions > 0) {
                $suspiciousPenalty = ($suspiciousActivities / $totalSessions) * $weights['suspicious_penalty_multiplier'];
            }

            $finalScore = ($completionRate * $weights['completion'])
                + ($progressPercentage * $weights['progress'])
                + ($attentionScore * $weights['attention'])
                + ($quizScore * $weights['quiz'])
                - $suspiciousPenalty;
        }

        return max(0.0, min(100.0, $finalScore));
    }

    /**
     * Risk level for a learner based on their average attention score,
     * using config-driven thresholds.
     */
    public function getRiskLevel(float $avgAttention): string
    {
        $levels = $this->attentionScoreConfigService->getActiveConfig()->config['risk_levels'];

        if ($avgAttention < $levels['high_below']) {
            return 'high';
        }

        if ($avgAttention < $levels['medium_below']) {
            return 'medium';
        }

        return 'low';
    }

    /**
     * Average attention score for an online course, read from the stored
     * learning_sessions.attention_score column. Traditional courses have no
     * sessions, so they get a neutral default.
     */
    public function getAttentionScore(int $userId, int $courseId, string $courseType): float
    {
        if ($courseType === 'traditional') {
            return 65.0;
        }

        $avg = DB::table('learning_sessions')
            ->where('user_id', $userId)
            ->where('course_online_id', $courseId)
            ->whereNotNull('attention_score')
            ->avg('attention_score');

        return $avg !== null ? round((float) $avg, 1) : 65.0;
    }

    /**
     * Best-attempt quiz percentage averaged across the course's quizzes.
     * Online quizzes are matched by quizzes.course_online_id, traditional by
     * quizzes.course_id.
     */
    public function getQuizScore(int $userId, int $courseId, string $courseType): float
    {
        $quizColumn = $courseType === 'online' ? 'course_online_id' : 'course_id';

        $attempts = DB::table('quiz_attempts')
            ->join('quizzes', 'quiz_attempts.quiz_id', '=', 'quizzes.id')
            ->where('quiz_attempts.user_id', $userId)
            ->where('quizzes.' . $quizColumn, $courseId)
            ->whereNotNull('quiz_attempts.completed_at')
            ->select([
                'quiz_attempts.quiz_id',
                DB::raw('MAX(quiz_attempts.total_score) as best_score'),
                DB::raw('MAX(quizzes.total_points) as total_points'),
            ])
            ->groupBy('quiz_attempts.quiz_id')
            ->get();

        if ($attempts->isEmpty()) {
            return 0.0;
        }

        $percentages = $attempts->map(function ($attempt) {
            if (($attempt->total_points ?? 0) > 0) {
                return ((float) $attempt->best_score / (float) $attempt->total_points) * 100;
            }
            return 0.0;
        });

        return round((float) $percentages->avg(), 1);
    }

    /**
     * Suspicious vs total session counts for an online course (for the penalty).
     *
     * @return array{suspicious:int, total:int}
     */
    public function getSessionStats(int $userId, int $courseId): array
    {
        $stats = DB::table('learning_sessions')
            ->where('user_id', $userId)
            ->where('course_online_id', $courseId)
            ->selectRaw('COUNT(*) as total, SUM(CASE WHEN is_suspicious = 1 THEN 1 ELSE 0 END) as suspicious')
            ->first();

        return [
            'suspicious' => (int) ($stats->suspicious ?? 0),
            'total'      => (int) ($stats->total ?? 0),
        ];
    }
}
