<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Seed the default config (matches the client's PDF spec exactly) and
     * backfill every existing learning_sessions / reporting_learning_sessions_fact
     * row to point at it, so historical data always has a known config version.
     */
    public function up(): void
    {
        $config = [
            'version' => 1,
            'video'   => [
                'weights' => [
                    'watch_time' => 40,
                    'engagement' => 40,
                    'completion' => 20,
                ],
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
            'risk_levels' => [
                'high_below'   => 50,
                'medium_below' => 70,
            ],
            'blended_score_weights' => [
                'completion'                    => 0.25,
                'progress'                       => 0.25,
                'attention'                      => 0.25,
                'quiz'                           => 0.25,
                'suspicious_penalty_multiplier' => 10,
            ],
        ];

        $configId = DB::table('attention_score_configs')->insertGetId([
            'name'       => 'Default (PDF Spec)',
            'is_active'  => true,
            'config'     => json_encode($config),
            'created_by' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('learning_sessions')
            ->whereNull('attention_score_config_id')
            ->update(['attention_score_config_id' => $configId]);

        DB::table('reporting_learning_sessions_fact')
            ->whereNull('attention_score_config_id')
            ->update(['attention_score_config_id' => $configId]);
    }

    public function down(): void
    {
        DB::table('learning_sessions')->update(['attention_score_config_id' => null]);
        DB::table('reporting_learning_sessions_fact')->update(['attention_score_config_id' => null]);
        DB::table('attention_score_configs')->where('name', 'Default (PDF Spec)')->delete();
    }
};
