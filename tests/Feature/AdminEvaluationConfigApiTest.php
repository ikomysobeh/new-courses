<?php

namespace Tests\Feature;

use App\Models\EvaluationConfig;
use App\Models\EvaluationHistory;
use App\Models\EvaluationType;
use App\Models\Department;
use App\Models\Evaluation;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminEvaluationConfigApiTest extends TestCase
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

    // ---- Config CRUD ----

    public function test_admin_can_create_evaluation_config(): void
    {
        $token = $this->adminToken();

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/admin/evaluation-configs/create', [
                'name'       => 'Attendance',
                'max_score'  => 5,
                'applies_to' => 'both',
            ]);

        $response->assertCreated()
            ->assertJsonPath('data.name', 'Attendance')
            ->assertJsonPath('data.max_score', 5)
            ->assertJsonPath('data.applies_to', 'both');

        $this->assertDatabaseHas('evaluation_configs', ['name' => 'Attendance']);
    }

    public function test_cannot_create_config_with_duplicate_name(): void
    {
        $token = $this->adminToken();
        EvaluationConfig::create(['name' => 'Attendance', 'max_score' => 5, 'applies_to' => 'both']);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/admin/evaluation-configs/create', [
                'name'       => 'Attendance',
                'max_score'  => 3,
                'applies_to' => 'regular',
            ]);

        $response->assertUnprocessable();
    }

    public function test_admin_can_update_evaluation_config(): void
    {
        $token  = $this->adminToken();
        $config = EvaluationConfig::create(['name' => 'Attendance', 'max_score' => 5, 'applies_to' => 'both']);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->putJson("/api/admin/evaluation-configs/update/{$config->id}", [
                'max_score' => 10,
            ]);

        $response->assertOk()->assertJsonPath('data.max_score', 10);
    }

    public function test_admin_can_delete_evaluation_config(): void
    {
        $token  = $this->adminToken();
        $config = EvaluationConfig::create(['name' => 'Attendance', 'max_score' => 5, 'applies_to' => 'both']);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->deleteJson("/api/admin/evaluation-configs/delete/{$config->id}");

        $response->assertOk();
        $this->assertDatabaseMissing('evaluation_configs', ['id' => $config->id]);
    }

    public function test_delete_config_blocked_when_referenced_in_history(): void
    {
        $token  = $this->adminToken();
        $config = EvaluationConfig::create(['name' => 'Attendance', 'max_score' => 5, 'applies_to' => 'both']);

        // Create a real evaluation so FK constraint is satisfied
        $dept = Department::create(['name' => 'Test Dept', 'slug' => 'test-dept']);
        $user = User::factory()->create(['department_id' => $dept->id]);
        $eval = Evaluation::create([
            'user_id'       => $user->id,
            'department_id' => $dept->id,
            'course_type'   => 'regular',
            'total_score'   => 5,
        ]);

        // Insert a history row that snapshots this config name
        EvaluationHistory::create([
            'evaluation_id' => $eval->id,
            'config_name'   => 'Attendance',
            'type_name'     => 'Full Attendance',
            'score_given'   => 5,
            'max_score'     => 5,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->deleteJson("/api/admin/evaluation-configs/delete/{$config->id}");

        $response->assertUnprocessable();
    }

    // ---- Type CRUD ----

    public function test_admin_can_add_type_to_config(): void
    {
        $token  = $this->adminToken();
        $config = EvaluationConfig::create(['name' => 'Attendance', 'max_score' => 5, 'applies_to' => 'both']);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson("/api/admin/evaluation-configs/{$config->id}/types/create", [
                'type_name'   => 'Full Attendance',
                'score_value' => 5,
            ]);

        $response->assertCreated()
            ->assertJsonPath('data.type_name', 'Full Attendance');

        $this->assertDatabaseHas('evaluation_types', ['evaluation_config_id' => $config->id]);
    }

    public function test_admin_can_update_evaluation_type(): void
    {
        $token  = $this->adminToken();
        $config = EvaluationConfig::create(['name' => 'Attendance', 'max_score' => 5, 'applies_to' => 'both']);
        $type   = EvaluationType::create(['evaluation_config_id' => $config->id, 'type_name' => 'Old', 'score_value' => 3]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->putJson("/api/admin/evaluation-types/update/{$type->id}", [
                'score_value' => 5,
            ]);

        $response->assertOk()->assertJsonPath('data.score_value', 5);
    }

    public function test_admin_can_delete_evaluation_type(): void
    {
        $token  = $this->adminToken();
        $config = EvaluationConfig::create(['name' => 'Attendance', 'max_score' => 5, 'applies_to' => 'both']);
        $type   = EvaluationType::create(['evaluation_config_id' => $config->id, 'type_name' => 'Old', 'score_value' => 3]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->deleteJson("/api/admin/evaluation-types/delete/{$type->id}");

        $response->assertOk();
        $this->assertDatabaseMissing('evaluation_types', ['id' => $type->id]);
    }
}
