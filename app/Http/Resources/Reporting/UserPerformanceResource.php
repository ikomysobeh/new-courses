<?php

namespace App\Http\Resources\Reporting;

use App\Http\Resources\BaseResource;
use Illuminate\Http\Request;

class UserPerformanceResource extends BaseResource
{
    public function toArray(Request $request): array
    {
        $totalAssignments = (int) $this->total_assignments;
        $completed        = (int) $this->completed_courses;
        $avgProgress      = round((float) $this->avg_progress, 2);
        $avgAttention     = round((float) $this->avg_attention, 1);
        $quizAttempts     = (int) $this->quiz_attempts_count;
        $avgQuizPct       = round((float) $this->avg_quiz_pct, 2);

        $completionRate = $totalAssignments > 0
            ? round($completed / $totalAssignments * 100, 2)
            : 0.0;

        $performanceScore = $this->performanceScore($avgProgress, $avgAttention, $completionRate, $avgQuizPct, $quizAttempts);

        return [
            'user_id'              => $this->user_id,
            'user_name'            => $this->user_name,
            'user_email'           => $this->user_email,
            'department_id'        => $this->department_id,
            'department_name'      => $this->department_name,
            'total_assignments'    => $totalAssignments,
            'completed_courses'    => $completed,
            'in_progress_courses'  => (int) $this->in_progress_courses,
            'completion_rate'      => $completionRate,
            'avg_progress'         => $avgProgress,
            // Explicit client-facing columns
            'progress'             => $avgProgress,
            'learning_time'        => $this->formatLearningTime((int) $this->total_active_seconds),
            'learning_time_seconds'=> (int) $this->total_active_seconds,
            'sessions_count'       => (int) $this->sessions_count,
            'total_active_seconds' => (int) $this->total_active_seconds,
            'avg_attention'        => $avgAttention,
            'suspicious_sessions'  => (int) $this->suspicious_sessions,
            'quiz_attempts_count'  => $quizAttempts,
            'quiz_passed_count'    => (int) $this->quiz_passed_count,
            'avg_quiz_pct'         => $avgQuizPct,
            'performance_score'    => $performanceScore,
            'performance_rating'   => $this->rating($performanceScore),
            'risk_level'           => $this->riskLevel((int) $this->suspicious_sessions, $avgAttention),
        ];
    }

    /**
     * Weighted score out of 100. Progress / attention / completion / quiz are
     * each weighted 25%. When the user has no quiz attempts, the quiz weight is
     * dropped and the remaining three are rescaled to 100%.
     */
    private function performanceScore(float $progress, float $attention, float $completion, float $quiz, int $quizAttempts): float
    {
        if ($quizAttempts > 0) {
            $score = $progress * 0.25 + $attention * 0.25 + $completion * 0.25 + $quiz * 0.25;
        } else {
            $score = $progress * (1 / 3) + $attention * (1 / 3) + $completion * (1 / 3);
        }

        return round($score, 2);
    }

    private function rating(float $score): string
    {
        return match (true) {
            $score >= 85 => 'excellent',
            $score >= 70 => 'good',
            $score >= 50 => 'average',
            default      => 'needs_improvement',
        };
    }

    /**
     * Format learning time (seconds) as a human-readable "Xh Ym" string.
     */
    private function formatLearningTime(int $seconds): string
    {
        if ($seconds <= 0) {
            return '0m';
        }

        $hours   = intdiv($seconds, 3600);
        $minutes = intdiv($seconds % 3600, 60);

        if ($hours > 0) {
            return $minutes > 0 ? "{$hours}h {$minutes}m" : "{$hours}h";
        }

        return "{$minutes}m";
    }

    private function riskLevel(int $suspicious, float $attention): string
    {
        return match (true) {
            $suspicious >= 3 || $attention < 50 => 'high',
            $suspicious >= 1 || $attention < 70 => 'medium',
            default                             => 'low',
        };
    }
}
