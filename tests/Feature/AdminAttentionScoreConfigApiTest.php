<?php

namespace Tests\Feature;

use App\Jobs\RecalculateAttentionScoresJob;
use App\Models\AttentionScoreConfig;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class AdminAttentionScoreConfigApiTest extends TestCase
{
    use RefreshDatabase;

    private function adminToken(): string
    {
        $this->seed(DatabaseSeeder::class);

        return (string) $this->postJson('/api/login', [
            'email'    => 'admin@newproject.test',
            'password' => env('ADMIN_INITIAL_PASSWORD', 'Admin@12345'),
        ])->json('data.token');
    }

    private function validConfigPayload(): array
    {
        return [
            'name'   => 'Adjusted Config',
            'config' => [
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
            ],
        ];
    }

    public function test_admin_can_get_active_config(): void
    {
        $token = $this->adminToken();

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/admin/attention-score-config/getActive')
            ->assertOk()
            ->assertJsonPath('data.is_active', true)
            ->assertJsonPath('data.name', 'Default (PDF Spec)');
    }

    public function test_preview_endpoint_computes_worked_examples_without_persisting(): void
    {
        $token = $this->adminToken();
        $before = AttentionScoreConfig::count();

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/admin/attention-score-config/preview', $this->validConfigPayload());

        $response->assertOk();
        $this->assertSame($before, AttentionScoreConfig::count());

        $examples = $response->json('examples');
        $this->assertSame(100, $examples[0]['result']['score']);
        $this->assertSame(85, $examples[1]['result']['score']);
        $this->assertSame(25, $examples[2]['result']['score']);
    }

    public function test_saving_a_config_creates_new_version_and_dispatches_recalculation(): void
    {
        Queue::fake();
        $token = $this->adminToken();

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/admin/attention-score-config/save', $this->validConfigPayload());

        $response->assertCreated()
            ->assertJsonPath('config.is_active', true)
            ->assertJsonPath('config.name', 'Adjusted Config');

        $this->assertDatabaseHas('attention_score_configs', ['name' => 'Adjusted Config', 'is_active' => 1]);
        $this->assertDatabaseHas('attention_score_configs', ['name' => 'Default (PDF Spec)', 'is_active' => 0]);

        Queue::assertPushed(RecalculateAttentionScoresJob::class);
    }

    public function test_saving_invalid_config_is_rejected(): void
    {
        $token = $this->adminToken();
        $payload = $this->validConfigPayload();
        $payload['config']['video']['weights']['watch_time'] = 10; // sums to 70, not 100

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/admin/attention-score-config/save', $payload)
            ->assertStatus(500);
    }

    public function test_non_admin_cannot_save_config(): void
    {
        $this->postJson('/api/admin/attention-score-config/save', $this->validConfigPayload())
            ->assertStatus(401);
    }
}
