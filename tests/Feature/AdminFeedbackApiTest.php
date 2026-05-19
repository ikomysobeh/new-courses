<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\EmployeeFeedback;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminFeedbackApiTest extends TestCase
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

    private function createFeedback(array $overrides = []): EmployeeFeedback
    {
        $user = User::factory()->create(['role' => 'user']);

        return EmployeeFeedback::create(array_merge([
            'user_id'     => $user->id,
            'type'        => 'suggestion',
            'title'       => 'Test feedback',
            'description' => 'Some description',
            'status'      => 'pending',
        ], $overrides));
    }

    // ---- getAll ----

    public function test_admin_can_list_all_feedback(): void
    {
        $token = $this->adminToken();
        $this->createFeedback();
        $this->createFeedback(['type' => 'general', 'status' => 'approved']);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/admin/feedback/getAll');

        $response->assertOk()
            ->assertJsonStructure(['data' => [['id', 'type', 'title', 'status', 'created_at']]]);
    }

    public function test_admin_can_filter_feedback_by_status(): void
    {
        $token = $this->adminToken();
        $this->createFeedback(['status' => 'pending']);
        $this->createFeedback(['status' => 'approved']);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/admin/feedback/getAll?status=approved');

        $response->assertOk();
        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertEquals('approved', $data[0]['status']);
    }

    public function test_admin_can_filter_feedback_by_type(): void
    {
        $token = $this->adminToken();
        $this->createFeedback(['type' => 'suggestion']);
        $this->createFeedback(['type' => 'general']);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/admin/feedback/getAll?type=general');

        $response->assertOk();
        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertEquals('general', $data[0]['type']);
    }

    public function test_admin_can_filter_feedback_by_user_id(): void
    {
        $token = $this->adminToken();
        $fb1   = $this->createFeedback();
        $this->createFeedback();

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/admin/feedback/getAll?user_id=' . $fb1->user_id);

        $response->assertOk();
        $data = $response->json('data');
        $this->assertCount(1, $data);
    }

    public function test_admin_can_search_feedback_by_title(): void
    {
        $token = $this->adminToken();
        $this->createFeedback(['title' => 'Login page broken']);
        $this->createFeedback(['title' => 'Dashboard looks great']);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/admin/feedback/getAll?search=Login');

        $response->assertOk();
        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertStringContainsString('Login', $data[0]['title']);
    }

    // ---- getById ----

    public function test_admin_can_view_single_feedback(): void
    {
        $token    = $this->adminToken();
        $feedback = $this->createFeedback();

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/admin/feedback/getById/' . $feedback->id);

        $response->assertOk()
            ->assertJsonPath('data.id', $feedback->id)
            ->assertJsonStructure(['data' => ['id', 'type', 'title', 'description', 'status', 'user']]);
    }

    public function test_admin_get_feedback_by_invalid_id_returns_404(): void
    {
        $token = $this->adminToken();

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/admin/feedback/getById/9999')
            ->assertNotFound();
    }

    // ---- respond ----

    public function test_admin_can_respond_to_feedback(): void
    {
        $token    = $this->adminToken();
        $feedback = $this->createFeedback(['status' => 'pending']);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->putJson('/api/admin/feedback/respond/' . $feedback->id, [
                'admin_response' => 'We will look into this.',
                'status'         => 'under_review',
            ]);

        $response->assertOk()
            ->assertJsonPath('data.status', 'under_review')
            ->assertJsonPath('data.admin_response', 'We will look into this.');

        $this->assertDatabaseHas('employee_feedback', [
            'id'             => $feedback->id,
            'status'         => 'under_review',
            'admin_response' => 'We will look into this.',
        ]);
    }

    public function test_respond_requires_admin_response_and_status(): void
    {
        $token    = $this->adminToken();
        $feedback = $this->createFeedback();

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->putJson('/api/admin/feedback/respond/' . $feedback->id, [])
            ->assertUnprocessable();
    }

    public function test_respond_rejects_invalid_status(): void
    {
        $token    = $this->adminToken();
        $feedback = $this->createFeedback();

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->putJson('/api/admin/feedback/respond/' . $feedback->id, [
                'admin_response' => 'OK',
                'status'         => 'invalid_status',
            ])
            ->assertUnprocessable();
    }

    // ---- status ----

    public function test_admin_can_update_feedback_status_only(): void
    {
        $token    = $this->adminToken();
        $feedback = $this->createFeedback(['status' => 'pending']);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->putJson('/api/admin/feedback/status/' . $feedback->id, [
                'status' => 'approved',
            ]);

        $response->assertOk()
            ->assertJsonPath('data.status', 'approved');

        $this->assertDatabaseHas('employee_feedback', [
            'id'     => $feedback->id,
            'status' => 'approved',
        ]);
    }

    public function test_status_update_rejects_invalid_value(): void
    {
        $token    = $this->adminToken();
        $feedback = $this->createFeedback();

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->putJson('/api/admin/feedback/status/' . $feedback->id, [
                'status' => 'deleted',
            ])
            ->assertUnprocessable();
    }

    // ---- activity log side-effect ----

    public function test_responding_to_feedback_creates_activity_log(): void
    {
        $token    = $this->adminToken();
        $feedback = $this->createFeedback();

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->putJson('/api/admin/feedback/respond/' . $feedback->id, [
                'admin_response' => 'Noted.',
                'status'         => 'approved',
            ]);

        $this->assertDatabaseHas('activity_logs', [
            'action'     => 'feedback.responded',
            'model_type' => EmployeeFeedback::class,
            'model_id'   => $feedback->id,
        ]);
    }
}
