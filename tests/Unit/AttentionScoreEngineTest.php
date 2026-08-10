<?php

namespace Tests\Unit;

use App\Models\AttentionScoreConfig;
use App\Services\AttentionScore\AttentionScoreEngine;
use PHPUnit\Framework\TestCase;

class AttentionScoreEngineTest extends TestCase
{
    private function defaultConfig(): AttentionScoreConfig
    {
        return new AttentionScoreConfig(['config' => [
            'video' => [
                'weights' => ['watch_time' => 40, 'engagement' => 40, 'completion' => 20],
                'time_ratio_bands' => [
                    ['min' => 0.00, 'max' => 0.30, 'points' => 0],
                    ['min' => 0.30, 'max' => 0.50, 'points' => 10],
                    ['min' => 0.50, 'max' => 0.80, 'points' => 25],
                    ['min' => 0.80, 'max' => 2.00, 'points' => 40],
                    ['min' => 2.00, 'max' => 2.50, 'points' => 35],
                    ['min' => 2.50, 'max' => 3.00, 'points' => 25],
                    ['min' => 3.00, 'max' => 4.00, 'points' => 10],
                    ['min' => 4.00, 'max' => null, 'points' => 0],
                ],
                'engagement_base_points' => 40,
                'speed_change_bands' => [
                    ['min' => 0, 'max' => 1,    'adjustment' => 0],
                    ['min' => 1, 'max' => 2,    'adjustment' => -5],
                    ['min' => 2, 'max' => 4,    'adjustment' => -10],
                    ['min' => 4, 'max' => null, 'adjustment' => -15],
                ],
                'completion_bands' => [
                    ['min' => 0,  'max' => 20,   'points' => 0],
                    ['min' => 20, 'max' => 50,   'points' => 5],
                    ['min' => 50, 'max' => 70,   'points' => 10],
                    ['min' => 70, 'max' => 90,   'points' => 15],
                    ['min' => 90, 'max' => null, 'points' => 20],
                ],
                'skip_ratio_bands' => [
                    ['max' => 5,    'adjustment' => 0],
                    ['max' => 15,   'adjustment' => -5],
                    ['max' => 30,   'adjustment' => -10],
                    ['max' => 50,   'adjustment' => -20],
                    ['max' => null, 'adjustment' => -30],
                ],
                'consistency_validation' => [
                    'completion_threshold' => 90,
                    'skip_ratio_threshold' => 30,
                    'penalty'              => -10,
                ],
                'allowed_review_window_multiplier' => 2.0,
            ],
            'risk_levels' => ['high_below' => 50, 'medium_below' => 70],
            'blended_score_weights' => [
                'completion' => 0.25, 'progress' => 0.25, 'attention' => 0.25, 'quiz' => 0.25,
                'suspicious_penalty_multiplier' => 10,
            ],
        ]]);
    }

    public function test_pdf_score_equals_completion_percentage(): void
    {
        $engine = new AttentionScoreEngine();

        $this->assertSame(45, $engine->calculatePdfScore(45));
        $this->assertSame(100, $engine->calculatePdfScore(150));
        $this->assertSame(0, $engine->calculatePdfScore(-10));
    }

    public function test_excellent_learner_scores_100(): void
    {
        $engine = new AttentionScoreEngine();

        $result = $engine->calculateVideoScore([
            'active_playback_time'      => 420,
            'video_duration'            => 300,
            'completion_percentage'     => 95,
            'speed_changes'             => 0,
            'unwatched_seconds_skipped' => 0,
        ], $this->defaultConfig());

        $this->assertSame(100, $result['score']);
    }

    public function test_moderate_learner_scores_85(): void
    {
        $engine = new AttentionScoreEngine();

        $result = $engine->calculateVideoScore([
            'active_playback_time'      => 240,
            'video_duration'            => 300,
            'completion_percentage'     => 80,
            'speed_changes'             => 1,
            'unwatched_seconds_skipped' => 30,
        ], $this->defaultConfig());

        $this->assertSame(85, $result['score']);
    }

    public function test_low_engagement_learner_scores_25(): void
    {
        $engine = new AttentionScoreEngine();

        $result = $engine->calculateVideoScore([
            'active_playback_time'      => 120,
            'video_duration'            => 300,
            'completion_percentage'     => 100,
            'speed_changes'             => 4,
            'unwatched_seconds_skipped' => 120,
        ], $this->defaultConfig());

        $this->assertSame(25, $result['score']);
    }

    public function test_different_config_produces_different_score_proving_no_hardcoded_values(): void
    {
        $engine = new AttentionScoreEngine();
        $config = $this->defaultConfig();

        // Halve every video weight/points value to prove the engine reads config, not constants.
        $altConfig = $config->config;
        $altConfig['video']['weights'] = ['watch_time' => 20, 'engagement' => 20, 'completion' => 10];
        foreach ($altConfig['video']['time_ratio_bands'] as &$b) { $b['points'] = (int) ($b['points'] / 2); }
        foreach ($altConfig['video']['completion_bands'] as &$b) { $b['points'] = (int) ($b['points'] / 2); }
        $altConfig['video']['engagement_base_points'] = 20;
        unset($b);

        $alt = new AttentionScoreConfig(['config' => $altConfig]);

        $metrics = [
            'active_playback_time' => 420, 'video_duration' => 300, 'completion_percentage' => 95,
            'speed_changes' => 0, 'unwatched_seconds_skipped' => 0,
        ];

        $defaultResult = $engine->calculateVideoScore($metrics, $config);
        $altResult     = $engine->calculateVideoScore($metrics, $alt);

        $this->assertSame(100, $defaultResult['score']);
        $this->assertSame(50, $altResult['score']);
    }

    public function test_rewatching_already_watched_content_is_not_penalized_as_a_skip(): void
    {
        $engine = new AttentionScoreEngine();

        // Watched 0-300 fully, then "skip" from 100 back to 200 (already watched) — no penalty.
        $segments = [[0.0, 300.0]];
        $unwatched = $engine->computeUnwatchedSecondsSkipped($segments, 100.0, 200.0);

        $this->assertSame(0.0, $unwatched);
    }

    public function test_skipping_over_unwatched_content_is_penalized(): void
    {
        $engine = new AttentionScoreEngine();

        // Watched 0-100 only, then jumps to 200 — the 100-200 gap was never watched.
        $segments = [[0.0, 100.0]];
        $unwatched = $engine->computeUnwatchedSecondsSkipped($segments, 100.0, 200.0);

        $this->assertSame(100.0, $unwatched);
    }

    public function test_merge_watched_segment_coalesces_overlapping_ranges(): void
    {
        $engine = new AttentionScoreEngine();

        $segments = $engine->mergeWatchedSegment([], 0, 100);
        $segments = $engine->mergeWatchedSegment($segments, 90, 200);
        $segments = $engine->mergeWatchedSegment($segments, 300, 400);

        $this->assertSame([[0.0, 200.0], [300.0, 400.0]], $segments);
    }
}
