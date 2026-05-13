<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Department;
use App\Models\EvaluationConfig;
use App\Models\EvaluationType;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Security tests: non-admin → 403 on admin endpoints, unauthenticated → 401
 */
class EvaluationSecurityTest extends TestCase
{
    use RefreshDatabase;

    private function userToken(): string
    {
        $this->seed(DatabaseSeeder::class);
        $user = User::factory()->create(['role' => 'user']);

        return (string) $this->postJson('/api/login', [
            'email'    => $user->email,
            'password' => 'password',
        ])->json('data.token');
    }

    // ---- Unauthenticated → 401 ----

    public function test_unauthenticated_cannot_access_admin_evaluation_configs(): void
    {
        $this->getJson('/api/admin/evaluation-configs/getAll')->assertUnauthorized();
    }

    public function test_unauthenticated_cannot_access_admin_evaluations(): void
    {
        $this->getJson('/api/admin/evaluations/getAll')->assertUnauthorized();
    }

    public function test_unauthenticated_cannot_access_admin_evaluation_history(): void
    {
        $this->getJson('/api/admin/evaluation-history/getAll')->assertUnauthorized();
    }

    public function test_unauthenticated_cannot_access_admin_notifications(): void
    {
        $this->getJson('/api/admin/evaluation-notifications/getAll')->assertUnauthorized();
    }

    // ---- Non-admin user → 403 ----

    public function test_non_admin_cannot_access_evaluation_configs(): void
    {
        $token = $this->userToken();

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/admin/evaluation-configs/getAll')
            ->assertForbidden();
    }

    public function test_non_admin_cannot_create_evaluation_config(): void
    {
        $token = $this->userToken();

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/admin/evaluation-configs/create', [
                'name'       => 'Injected',
                'max_score'  => 5,
                'applies_to' => 'both',
            ])
            ->assertForbidden();
    }

    public function test_non_admin_cannot_create_evaluation(): void
    {
        $token = $this->userToken();

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/admin/evaluations/create', [
                'user_id'       => 1,
                'department_id' => 1,
                'course_type'   => 'regular',
                'course_id'     => 1,
                'scores'        => [],
            ])
            ->assertForbidden();
    }

    public function test_non_admin_cannot_send_evaluation_notification(): void
    {
        $token = $this->userToken();

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/admin/evaluation-notifications/send', [
                'manager_ids' => [1],
                'subject'     => 'Test',
                'message'     => 'Body',
            ])
            ->assertForbidden();
    }

    public function test_non_admin_cannot_access_evaluation_history_analytics(): void
    {
        $token = $this->userToken();

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/admin/evaluation-history/analytics')
            ->assertForbidden();
    }

    public function test_non_admin_cannot_export_history_csv(): void
    {
        $token = $this->userToken();

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/admin/evaluation-history/export')
            ->assertForbidden();
    }
}
