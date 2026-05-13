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

    public function test_get_all_assignments_returns_cards(): void
    {
        $token  = $this->adminToken();
        $course = $this->createCourse();
        $admin  = $this->admin();
        $user1  = User::factory()->create(['role' => 'user']);
        $user2  = User::factory()->create(['role' => 'user']);

        CourseOnlineAssignment::query()->create(['course_online_id' => $course->id, 'user_id' => $user1->id, 'assigned_by' => $admin->id, 'assigned_at' => now()]);
        CourseOnlineAssignment::query()->create(['course_online_id' => $course->id, 'user_id' => $user2->id, 'assigned_by' => $admin->id, 'assigned_at' => now()]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/admin/online-course-assignments/getAll');

        $response->assertOk()
            ->assertJsonStructure(['data', 'meta', 'cards']);

        $cards = collect($response->json('cards'));
        $this->assertNotNull($cards->firstWhere('key', 'total_assignments'));
        $this->assertSame(2, $response->json('meta.total'));
    }

    // -------------------------------------------------------------------------
    // create
    // -------------------------------------------------------------------------

    public function test_admin_can_assign_multiple_users_to_course(): void
    {
        $token  = $this->adminToken();
        $course = $this->createCourse();
        $user2  = User::factory()->create(['role' => 'user']);
        $user3  = User::factory()->create(['role' => 'user']);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/admin/online-course-assignments/create', [
                'course_online_id' => $course->id,
                'user_ids'         => [$user2->id, $user3->id],
            ]);

        $response->assertCreated();
        $this->assertSame(2, $response->json('meta.created'));
        $this->assertSame(0, $response->json('meta.skipped'));
        $this->assertDatabaseCount('course_online_assignments', 2);
    }

    public function test_duplicate_assignment_is_skipped_not_errored(): void
    {
        $token  = $this->adminToken();
        $admin  = $this->admin();
        $course = $this->createCourse();
        $user2  = User::factory()->create(['role' => 'user']);

        // First assignment
        CourseOnlineAssignment::query()->create([
            'course_online_id' => $course->id,
            'user_id'          => $user2->id,
            'assigned_by'      => $admin->id,
            'assigned_at'      => now(),
        ]);

        // Second attempt — same user
        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/admin/online-course-assignments/create', [
                'course_online_id' => $course->id,
                'user_ids'         => [$user2->id],
            ]);

        $response->assertCreated();
        $this->assertSame(0, $response->json('meta.created'));
        $this->assertSame(1, $response->json('meta.skipped'));

        // DB still has only 1 assignment row
        $this->assertDatabaseCount('course_online_assignments', 1);
    }

    public function test_admin_can_unassign_user(): void
    {
        $token  = $this->adminToken();
        $admin  = $this->admin();
        $course = $this->createCourse();
        $user   = User::factory()->create(['role' => 'user']);

        $assignment = CourseOnlineAssignment::query()->create([
            'course_online_id' => $course->id,
            'user_id'          => $user->id,
            'assigned_by'      => $admin->id,
            'assigned_at'      => now(),
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->deleteJson("/api/admin/online-course-assignments/delete/{$assignment->id}");

        $response->assertNoContent();
        $this->assertSoftDeleted('course_online_assignments', ['id' => $assignment->id]);

        $assignment->refresh();
        $this->assertNotNull($assignment->unassigned_at);
        $this->assertSame($admin->id, $assignment->unassigned_by);
    }

    // -------------------------------------------------------------------------
    // Auth guard
    // -------------------------------------------------------------------------

    public function test_unauthenticated_request_is_rejected(): void
    {
        $this->getJson('/api/admin/online-course-assignments/getAll')->assertUnauthorized();
    }
}