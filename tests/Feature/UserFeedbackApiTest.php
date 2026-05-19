<?php

namespace Tests\Feature;

use App\Models\EmployeeFeedback;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserFeedbackApiTest extends TestCase
{
    use RefreshDatabase;

    private function userToken(User $user): string
    {
        return (string) $this->postJson('/api/login', [
            'email'    => $user->email,
            'password' => 'password',
        ])->json('data.token');
    }

    private function seedAndGetUser(): User
    {
        $this->seed(DatabaseSeeder::class);

        return User::factory()->create(['role' => 'user']);
    }

    // ---- create ----

    public function test_user_can_submit_feedback(): void
    {
        $user  = $this->seedAndGetUser();
        $token = $this->userToken($user);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/user/feedback/create', [
                'type'        => 'suggestion',
                'title'       => 'Better search',
                'description' => 'The search bar is hard to find.',
            ]);

        $response->assertCreated()
            ->assertJsonPath('data.type', 'suggestion')
            ->assertJsonPath('data.title', 'Better search')
            ->assertJsonPath('data.status', 'pending');

        $this->assertDatabaseHas('employee_feedback', [
            'user_id' => $user->id,
            'title'   => 'Better search',
        ]);
    }

    public function test_user_id_is_set_from_auth_not_request_body(): void
    {
        $user  = $this->seedAndGetUser();
        $token = $this->userToken($user);

        $otherUser = User::factory()->create();

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/user/feedback/create', [
                'type'        => 'general',
                'title'       => 'Test',
                'description' => 'Test body',
                'user_id'     => $otherUser->id, // should be ignored
            ]);

        $this->assertDatabaseMissing('employee_feedback', [
            'user_id' => $otherUser->id,
            'title'   => 'Test',
        ]);
        $this->assertDatabaseHas('employee_feedback', [
            'user_id' => $user->id,
            'title'   => 'Test',
        ]);
    }

    public function test_submit_feedback_requires_type_title_description(): void
    {
        $user  = $this->seedAndGetUser();
        $token = $this->userToken($user);

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/user/feedback/create', [])
            ->assertUnprocessable();
    }

    public function test_submit_feedback_rejects_invalid_type(): void
    {
        $user  = $this->seedAndGetUser();
        $token = $this->userToken($user);

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/user/feedback/create', [
                'type'        => 'complaint', // not in enum
                'title'       => 'Title',
                'description' => 'Body',
            ])
            ->assertUnprocessable();
    }

    // ---- getAll ----

    public function test_user_can_list_own_feedback(): void
    {
        $user  = $this->seedAndGetUser();
        $token = $this->userToken($user);

        EmployeeFeedback::create([
            'user_id' => $user->id, 'type' => 'suggestion',
            'title' => 'Mine', 'description' => 'Desc', 'status' => 'pending',
        ]);

        $otherUser = User::factory()->create();
        EmployeeFeedback::create([
            'user_id' => $otherUser->id, 'type' => 'general',
            'title' => 'Not mine', 'description' => 'Desc', 'status' => 'pending',
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/user/feedback/getAll');

        $response->assertOk();
        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertEquals('Mine', $data[0]['title']);
    }

    public function test_user_can_filter_own_feedback_by_status(): void
    {
        $user  = $this->seedAndGetUser();
        $token = $this->userToken($user);

        EmployeeFeedback::create([
            'user_id' => $user->id, 'type' => 'suggestion',
            'title' => 'Pending one', 'description' => 'Desc', 'status' => 'pending',
        ]);
        EmployeeFeedback::create([
            'user_id' => $user->id, 'type' => 'general',
            'title' => 'Approved one', 'description' => 'Desc', 'status' => 'approved',
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/user/feedback/getAll?status=pending');

        $response->assertOk();
        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertEquals('pending', $data[0]['status']);
    }

    // ---- getById ----

    public function test_user_can_view_own_feedback_by_id(): void
    {
        $user  = $this->seedAndGetUser();
        $token = $this->userToken($user);

        $feedback = EmployeeFeedback::create([
            'user_id' => $user->id, 'type' => 'suggestion',
            'title' => 'My Feedback', 'description' => 'Desc', 'status' => 'pending',
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/user/feedback/getById/' . $feedback->id);

        $response->assertOk()
            ->assertJsonPath('data.id', $feedback->id)
            ->assertJsonPath('data.title', 'My Feedback');
    }

    public function test_user_cannot_view_another_users_feedback(): void
    {
        $user  = $this->seedAndGetUser();
        $token = $this->userToken($user);

        $otherUser = User::factory()->create();
        $feedback  = EmployeeFeedback::create([
            'user_id' => $otherUser->id, 'type' => 'general',
            'title' => 'Not yours', 'description' => 'Desc', 'status' => 'pending',
        ]);

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/user/feedback/getById/' . $feedback->id)
            ->assertForbidden();
    }

    // ---- activity log side-effect ----

    public function test_submitting_feedback_creates_activity_log(): void
    {
        $user  = $this->seedAndGetUser();
        $token = $this->userToken($user);

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/user/feedback/create', [
                'type'        => 'suggestion',
                'title'       => 'Track this',
                'description' => 'Description here',
            ]);

        $this->assertDatabaseHas('activity_logs', [
            'action'  => 'feedback.submitted',
            'user_id' => $user->id,
        ]);
    }
}
