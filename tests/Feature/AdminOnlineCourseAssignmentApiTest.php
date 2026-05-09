<?php

namespace Tests\Feature;

use App\Models\CourseOnline;
use App\Models\CourseOnlineAssignment;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminOnlineCourseAssignmentApiTest extends TestCase
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

    private function admin(): User
    {
        return User::query()->where('email', 'admin@newproject.test')->firstOrFail();
    }

    private function createCourse(): CourseOnline
    {
        return CourseOnline::query()->create([
            'name'       => 'Test Online Course',
            'status'     => 'published',
            'created_by' => $this->admin()->id,
        ]);
    }

    // -------------------------------------------------------------------------
    // getAll
    // -------------------------------------------------------------------------

    public function test_get_all_assignments_returns_list_with_cards(): void
    {
        $token  = $this->adminToken();
        $course = $this->createCourse();
        $user   = User::factory()->create(['role' => 'user']);

        CourseOnlineAssignment::query()->create([
            'course_online_id' => $course->id,
            'user_id'          => $user->id,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/admin/online-course-assignments/getAll');

        $response->assertOk()
            ->assertJsonStructure([
                'data',
                'meta',
                'cards' => [
                    '*' => ['key', 'title', 'value'],
                ],
            ]);

        $this->assertSame(1, $response->json('meta.total'));
    }

    // -------------------------------------------------------------------------
    // create
    // -------------------------------------------------------------------------

    public function test_create_assignment(): void
    {
        $token  = $this->adminToken();
        $course = $this->createCourse();
        $user   = User::factory()->create(['role' => 'user']);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/admin/online-course-assignments/create', [
                'course_online_id' => $course->id,
                'user_id'          => $user->id,
            ]);

        $response->assertCreated()
            ->assertJsonPath('data.course_online_id', $course->id)
            ->assertJsonPath('data.user_id', $user->id);

        $this->assertDatabaseHas('course_online_assignments', [
            'course_online_id' => $course->id,
            'user_id'          => $user->id,
        ]);
    }

    public function test_create_assignment_rejects_duplicate(): void
    {
        $token  = $this->adminToken();
        $course = $this->createCourse();
        $user   = User::factory()->create(['role' => 'user']);

        CourseOnlineAssignment::query()->create([
            'course_online_id' => $course->id,
            'user_id'          => $user->id,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/admin/online-course-assignments/create', [
                'course_online_id' => $course->id,
                'user_id'          => $user->id,
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['user_id']);
    }

    public function test_create_assignment_requires_fields(): void
    {
        $token = $this->adminToken();

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/admin/online-course-assignments/create', []);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['course_online_id', 'user_id']);
    }

    // -------------------------------------------------------------------------
    // delete
    // -------------------------------------------------------------------------

    public function test_delete_assignment(): void
    {
        $token  = $this->adminToken();
        $course = $this->createCourse();
        $user   = User::factory()->create(['role' => 'user']);

        $assignment = CourseOnlineAssignment::query()->create([
            'course_online_id' => $course->id,
            'user_id'          => $user->id,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->deleteJson("/api/admin/online-course-assignments/delete/{$assignment->id}");

        $response->assertOk()
            ->assertJsonPath('message', 'Assignment deleted successfully.');

        $this->assertSoftDeleted('course_online_assignments', ['id' => $assignment->id]);
    }

    // -------------------------------------------------------------------------
    // Auth guard
    // -------------------------------------------------------------------------

    public function test_unauthenticated_request_is_rejected(): void
    {
        $this->getJson('/api/admin/online-course-assignments/getAll')->assertUnauthorized();
    }
}
