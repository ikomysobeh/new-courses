<?php

namespace Tests\Feature;

use App\Events\CourseAssigned;
use App\Models\Course;
use App\Models\CourseAssignment;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class AdminCourseAssignmentApiTest extends TestCase
{
    use RefreshDatabase;

    private function adminToken(): string
    {
        $this->seed(DatabaseSeeder::class);

        return (string) $this->postJson('/api/login', [
            'email' => 'admin@newproject.test',
            'password' => env('ADMIN_INITIAL_PASSWORD', 'Admin@12345'),
        ])->json('data.token');
    }

    private function createCourse(): Course
    {
        $admin = User::query()->where('role', 'admin')->first();

        return Course::query()->create([
            'name'       => 'Assignment Test Course',
            'status'     => 'published',
            'privacy'    => 'private',
            'created_by' => $admin->id,
        ]);
    }

    public function test_admin_can_assign_course_to_user(): void
    {
        Event::fake();
        $token  = $this->adminToken();
        $course = $this->createCourse();
        $user   = User::factory()->create(['role' => 'user']);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/admin/course-assignments/create', [
                'course_id' => $course->id,
                'user_id'   => $user->id,
            ]);

        $response->assertCreated();
        $this->assertDatabaseHas('course_assignments', [
            'course_id' => $course->id,
            'user_id'   => $user->id,
        ]);

        Event::assertDispatched(CourseAssigned::class);
    }

    public function test_admin_can_get_all_course_assignments_with_cards(): void
    {
        Event::fake();
        $token  = $this->adminToken();
        $course = $this->createCourse();
        $user   = User::factory()->create(['role' => 'user']);
        $admin  = User::query()->where('role', 'admin')->first();

        CourseAssignment::query()->create([
            'course_id'   => $course->id,
            'user_id'     => $user->id,
            'assigned_by' => $admin->id,
            'assigned_at' => now(),
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/admin/course-assignments/getAll');

        $response->assertOk()->assertJsonStructure([
            'data',
            'meta',
            'cards' => [
                '*' => ['key', 'title', 'value'],
            ],
        ]);
        $response->assertJsonPath('cards.0.key', 'total_course_assignments');
    }

    public function test_admin_cannot_create_duplicate_assignment(): void
    {
        Event::fake();
        $token  = $this->adminToken();
        $course = $this->createCourse();
        $user   = User::factory()->create(['role' => 'user']);

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/admin/course-assignments/create', [
                'course_id' => $course->id,
                'user_id'   => $user->id,
            ])->assertCreated();

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/admin/course-assignments/create', [
                'course_id' => $course->id,
                'user_id'   => $user->id,
            ])->assertUnprocessable();

        $this->assertDatabaseCount('course_assignments', 1);
    }

    public function test_admin_can_delete_assignment(): void
    {
        Event::fake();
        $token  = $this->adminToken();
        $course = $this->createCourse();
        $user   = User::factory()->create(['role' => 'user']);
        $admin  = User::query()->where('role', 'admin')->first();

        $assignment = CourseAssignment::query()->create([
            'course_id'   => $course->id,
            'user_id'     => $user->id,
            'assigned_by' => $admin->id,
            'assigned_at' => now(),
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->deleteJson('/api/admin/course-assignments/delete/' . $assignment->id);

        $response->assertOk();
        $this->assertDatabaseMissing('course_assignments', ['id' => $assignment->id]);
    }

    public function test_assignment_dispatches_course_assigned_event(): void
    {
        Event::fake();
        $token  = $this->adminToken();
        $course = $this->createCourse();
        $user   = User::factory()->create(['role' => 'user']);

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/admin/course-assignments/create', [
                'course_id' => $course->id,
                'user_id'   => $user->id,
            ])->assertCreated();

        Event::assertDispatched(CourseAssigned::class, function (CourseAssigned $event) use ($course, $user) {
            return $event->course->id === $course->id && $event->assignedUser->id === $user->id;
        });
    }

    public function test_admin_can_resend_login_link(): void
    {
        Event::fake();
        Mail::fake();

        $token  = $this->adminToken();
        $course = $this->createCourse();
        $admin  = User::query()->where('role', 'admin')->first();
        $user   = User::factory()->create(['role' => 'user', 'email' => 'resend@example.com']);

        $assignment = CourseAssignment::query()->create([
            'course_id'   => $course->id,
            'user_id'     => $user->id,
            'assigned_by' => $admin->id,
            'assigned_at' => now(),
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/admin/course-assignments/' . $assignment->id . '/resend-link');

        $response->assertOk()
            ->assertJsonPath('message', 'Login link resent successfully.');

        Mail::assertQueued(\App\Mail\CourseAssignedUserMail::class, function ($mail) use ($user) {
            return $mail->hasTo($user->email);
        });

        // Login token should be refreshed
        $this->assertNotNull($user->fresh()->login_token);
    }

    public function test_resend_link_returns_404_for_invalid_assignment(): void
    {
        $token = $this->adminToken();

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/admin/course-assignments/9999/resend-link');

        $response->assertNotFound();
    }
}
