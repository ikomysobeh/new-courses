<?php

namespace App\Services\AttentionScore;

use App\Models\AttentionScoreConfig;

/**
 * Pure calculation engine for the Attention Score formula.
 *
 * No hardcoded numbers live here — every band/weight/threshold is read
 * from the given AttentionScoreConfig, so the same code path serves both
 * live scoring (LearningSessionService) and historical recalculation
 * (AttentionScoreRecalculationService).
 */
class AttentionScoreEngine
{
    /**
     * PDF content: attention = completion percentage, clamped 0-100.
     * Not config-driven per product decision.
     */
    public function calculatePdfScore(float $completionPercentage): int
    {
        return (int) max(0, min(100, $completionPercentage));
    }

    /**
     * Video content: Watch Time & Consistency + Engagement + Completion
     * - Adjustments (skip ratio + consistency validation), clamped 0-100.
     *
     * @param array{
     *   active_playback_time: float,
     *   video_duration: float,
     *   completion_percentage: float,
     *   speed_changes: int,
     *   unwatched_seconds_skipped: float,
     * } $metrics
     * @return array{score:int, breakdown:array}
     */
    public function calculateVideoScore(array $metrics, AttentionScoreConfig $config): array
    {
        $video = $config->config['video'];

        $duration   = (float) $metrics['video_duration'];
        $completion = (float) $metrics['completion_percentage'];

        $timeRatio = $duration > 0
            ? ((float) $metrics['active_playback_time']) / $duration
            : 0.0;

        $watchTimePoints = $this->matchMinMaxBand($timeRatio, $video['time_ratio_bands'], 'points');

        $engagementBase       = (float) $video['engagement_base_points'];
        $speedChangeAdjustment = $this->matchMinMaxBand(
            (float) $metrics['speed_changes'],
            $video['speed_change_bands'],
            'adjustment'
        );
        $engagementPoints = $engagementBase + $speedChangeAdjustment;

        $completionPoints = $this->matchMinMaxBand($completion, $video['completion_bands'], 'points');

        $skipRatio = $duration > 0
            ? (((float) $metrics['unwatched_seconds_skipped']) / $duration) * 100
            : 0.0;

        $skipAdjustment = $this->matchThresholdBand($skipRatio, $video['skip_ratio_bands'], 'adjustment');

        $consistency = $video['consistency_validation'];
        $consistencyAdjustment = 0.0;
        if ($completion >= $consistency['completion_threshold'] && $skipRatio > $consistency['skip_ratio_threshold']) {
            $consistencyAdjustment = (float) $consistency['penalty'];
        }

        $totalAdjustments = $skipAdjustment + $consistencyAdjustment;

        $total = $watchTimePoints + $engagementPoints + $completionPoints + $totalAdjustments;
        $score = (int) max(0, min(100, round($total)));

        return [
            'score'     => $score,
            'breakdown' => [
                'time_ratio'              => round($timeRatio, 4),
                'watch_time_points'       => $watchTimePoints,
                'engagement_points'       => $engagementPoints,
                'speed_change_adjustment' => $speedChangeAdjustment,
                'completion_points'       => $completionPoints,
                'skip_ratio'              => round($skipRatio, 2),
                'skip_adjustment'         => $skipAdjustment,
                'consistency_adjustment'  => $consistencyAdjustment,
                'total_before_clamp'      => $total,
            ],
        ];
    }

    /**
     * unwatched_seconds_skipped: seconds of content jumped over that were
     * never previously present in $watchedSegments. Content already
     * covered by a prior segment (replay/rewatch) is never penalized.
     *
     * @param array<array{0:float,1:float}> $watchedSegments merged [start, end] ranges
     */
    public function computeUnwatchedSecondsSkipped(array $watchedSegments, float $fromPosition, float $toPosition): float
    {
        if ($toPosition <= $fromPosition) {
            return 0.0;
        }

        $jumpStart = $fromPosition;
        $jumpEnd   = $toPosition;
        $covered   = 0.0;

        foreach ($watchedSegments as [$segStart, $segEnd]) {
            $overlapStart = max($jumpStart, $segStart);
            $overlapEnd   = min($jumpEnd, $segEnd);
            if ($overlapEnd > $overlapStart) {
                $covered += $overlapEnd - $overlapStart;
            }
        }

        return max(0.0, ($jumpEnd - $jumpStart) - $covered);
    }

    /**
     * Merges a newly-played [start, end] interval into the existing
     * watched-segments map, coalescing overlapping/adjacent ranges.
     *
     * @param array<array{0:float,1:float}> $segments
     * @return array<array{0:float,1:float}>
     */
    public function mergeWatchedSegment(array $segments, float $start, float $end): array
    {
        if ($end <= $start) {
            return $segments;
        }

        $segments[] = [$start, $end];
        usort($segments, fn ($a, $b) => $a[0] <=> $b[0]);

        $merged = [];
        foreach ($segments as $segment) {
            if (empty($merged) || $segment[0] > $merged[count($merged) - 1][1]) {
                $merged[] = $segment;
            } else {
                $last = &$merged[count($merged) - 1];
                $last[1] = max($last[1], $segment[1]);
                unset($last);
            }
        }

        return $merged;
    }

    /**
     * Matches a value against ordered {min, max, ...} bands (max=null on
     * the last band means open-ended). Returns the requested field.
     */
    private function matchMinMaxBand(float $value, array $bands, string $field): float
    {
        foreach ($bands as $band) {
            $min = (float) $band['min'];
            $max = $band['max'];

            if ($value >= $min && ($max === null || $value < $max)) {
                return (float) $band[$field];
            }
        }

        // Value exceeds every explicit band (e.g. equals the last band's max
        // when max isn't null) — fall back to the highest band.
        return (float) end($bands)[$field];
    }

    /**
     * Matches a value against ordered {max, ...} threshold bands where a
     * band applies when value <= max (max=null on the last band means
     * "greater than every prior threshold").
     */
    private function matchThresholdBand(float $value, array $bands, string $field): float
    {
        foreach ($bands as $band) {
            if ($band['max'] === null || $value <= (float) $band['max']) {
                return (float) $band[$field];
            }
        }

        return (float) end($bands)[$field];
    }
}
