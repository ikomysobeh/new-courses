<?php

namespace Tests\Feature;

use App\Events\UserEnrolledInPublicCourse;
use App\Models\Course;
use App\Models\CourseAssignment;
use App\Models\CourseAvailability;
use App\Models\CourseRegistration;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class UserCourseApiTest extends TestCase
{
    use RefreshDatabase;

    private function userToken(User $user): string
    {
        return (string) $this->postJson('/api/login', [
            'email'    => $user->email,
            'password' => 'password',
        ])->json('data.token');
    }

    private function createPublicCourse(array $overrides = []): Course
    {
        $admin = User::query()->where('role', 'admin')->first();

        return Course::query()->create(array_merge([
            'name'       => 'Public Course',
            'status'     => 'published',
            'privacy'    => 'public',
            'created_by' => $admin->id,
        ], $overrides));
    }

    private function createAvailability(Course $course, array $overrides = []): CourseAvailability
    {
        return CourseAvailability::query()->create(array_merge([
            'course_id'  => $course->id,
            'start_date' => now()->addDay(),
            'end_date'   => now()->addDays(30),
            'capacity'   => 10,
            'sessions'   => 10,
            'status'     => 'active',
        ], $overrides));
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_user_can_see_public_courses_in_list(): void
    {
        $user   = User::factory()->create(['role' => 'user']);
        $token  = $this->userToken($user);
        $course = $this->createPublicCourse();

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/user/courses/getAll');

        $response->assertOk();
        $courseIds = collect($response->json('data'))->pluck('id');
        $this->assertTrue($courseIds->contains($course->id));
    }

    public function test_user_can_see_private_course_only_if_assigned(): void
    {
        $admin  = User::query()->where('role', 'admin')->first();
        $user   = User::factory()->create(['role' => 'user']);
        $token  = $this->userToken($user);

        $privateCourse = Course::query()->create([
            'name'       => 'Private Only',
            'status'     => 'published',
            'privacy'    => 'private',
            'created_by' => $admin->id,
        ]);

        // Without assignment — should not appear
        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/user/courses/getAll');

        $courseIds = collect($response->json('data'))->pluck('id');
        $this->assertFalse($courseIds->contains($privateCourse->id));

        // Assign, then it should appear
        CourseAssignment::query()->create([
            'course_id'   => $privateCourse->id,
            'user_id'     => $user->id,
            'assigned_by' => $admin->id,
            'assigned_at' => now(),
        ]);

        $response2 = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/user/courses/getAll');

        $courseIds2 = collect($response2->json('data'))->pluck('id');
        $this->assertTrue($courseIds2->contains($privateCourse->id));
    }

    public function test_user_cannot_get_private_course_detail_without_assignment(): void
    {
        $admin  = User::query()->where('role', 'admin')->first();
        $user   = User::factory()->create(['role' => 'user']);
        $token  = $this->userToken($user);

        $privateCourse = Course::query()->create([
            'name'       => 'Secret',
            'status'     => 'published',
            'privacy'    => 'private',
            'created_by' => $admin->id,
        ]);

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/user/courses/getById/' . $privateCourse->id)
            ->assertUnprocessable();
    }

    public function test_user_can_enroll_in_course_with_available_spots(): void
    {
        Event::fake();
        $user         = User::factory()->create(['role' => 'user']);
        $token        = $this->userToken($user);
        $course       = $this->createPublicCourse();
        $availability = $this->createAvailability($course);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/user/courses/enroll/' . $course->id, [
                'course_availability_id' => $availability->id,
            ]);

        $response->assertCreated();
        $this->assertDatabaseHas('course_registrations', [
            'user_id'   => $user->id,
            'course_id' => $course->id,
            'status'    => 'in_progress',
        ]);
        $this->assertDatabaseHas('course_availabilities', [
            'id'       => $availability->id,
            'sessions' => 9,
        ]);

        Event::assertDispatched(UserEnrolledInPublicCourse::class);
    }

    public function test_user_cannot_enroll_twice_in_same_course(): void
    {
        Event::fake();
        $user         = User::factory()->create(['role' => 'user']);
        $token        = $this->userToken($user);
        $course       = $this->createPublicCourse();
        $availability = $this->createAvailability($course);

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/user/courses/enroll/' . $course->id, [
                'course_availability_id' => $availability->id,
            ])->assertCreated();

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/user/courses/enroll/' . $course->id, [
                'course_availability_id' => $availability->id,
            ])->assertUnprocessable();

        $this->assertDatabaseCount('course_registrations', 1);
    }

    public function test_user_cannot_enroll_when_availability_is_full(): void
    {
        Event::fake();
        $user         = User::factory()->create(['role' => 'user']);
        $token        = $this->userToken($user);
        $course       = $this->createPublicCourse();
        $availability = $this->createAvailability($course, ['sessions' => 0]);

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/user/courses/enroll/' . $course->id, [
                'course_availability_id' => $availability->id,
            ])->assertUnprocessable();
    }

    public function test_user_cannot_enroll_in_closed_availability(): void
    {
        Event::fake();
        $user         = User::factory()->create(['role' => 'user']);
        $token        = $this->userToken($user);
        $course       = $this->createPublicCourse();
        $availability = $this->createAvailability($course, ['status' => 'closed']);

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/user/courses/enroll/' . $course->id, [
                'course_availability_id' => $availability->id,
            ])->assertUnprocessable();
    }

    public function test_user_cannot_enroll_in_expired_availability(): void
    {
        Event::fake();
        $user         = User::factory()->create(['role' => 'user']);
        $token        = $this->userToken($user);
        $course       = $this->createPublicCourse();
        $availability = $this->createAvailability($course, [
            'start_date' => now()->subDays(30),
            'end_date'   => now()->subDay(),
        ]);

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/user/courses/enroll/' . $course->id, [
                'course_availability_id' => $availability->id,
            ])->assertUnprocessable();
    }

    public function test_user_can_complete_course(): void
    {
        Event::fake();
        $user         = User::factory()->create(['role' => 'user']);
        $token        = $this->userToken($user);
        $course       = $this->createPublicCourse();
        $availability = $this->createAvailability($course);

        CourseRegistration::query()->create([
            'user_id'                => $user->id,
            'course_id'              => $course->id,
            'course_availability_id' => $availability->id,
            'status'                 => 'in_progress',
            'registered_at'          => now(),
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/user/courses/complete/' . $course->id);

        $response->assertOk()->assertJsonPath('data.status', 'completed');
        $this->assertDatabaseHas('course_registrations', ['user_id' => $user->id, 'status' => 'completed']);
        $this->assertDatabaseHas('course_completions', ['user_id' => $user->id, 'course_id' => $course->id]);
    }

    public function test_user_cannot_complete_course_if_not_enrolled(): void
    {
        $user   = User::factory()->create(['role' => 'user']);
        $token  = $this->userToken($user);
        $course = $this->createPublicCourse();

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/user/courses/complete/' . $course->id)
            ->assertNotFound();
    }

    public function test_user_cannot_complete_course_twice(): void
    {
        Event::fake();
        $user         = User::factory()->create(['role' => 'user']);
        $token        = $this->userToken($user);
        $course       = $this->createPublicCourse();
        $availability = $this->createAvailability($course);

        CourseRegistration::query()->create([
            'user_id'                => $user->id,
            'course_id'              => $course->id,
            'course_availability_id' => $availability->id,
            'status'                 => 'completed',
            'registered_at'          => now(),
            'completed_at'           => now(),
        ]);

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/user/courses/complete/' . $course->id)
            ->assertUnprocessable();
    }

    public function test_user_can_submit_rating_after_completion(): void
    {
        Event::fake();
        $user         = User::factory()->create(['role' => 'user']);
        $token        = $this->userToken($user);
        $course       = $this->createPublicCourse();
        $availability = $this->createAvailability($course);

        CourseRegistration::query()->create([
            'user_id'                => $user->id,
            'course_id'              => $course->id,
            'course_availability_id' => $availability->id,
            'status'                 => 'completed',
            'registered_at'          => now(),
            'completed_at'           => now(),
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/user/courses/submitRating/' . $course->id, [
                'rating'   => 5,
                'feedback' => 'Excellent course!',
            ]);

        $response->assertOk()->assertJsonPath('data.rating', 5);
        $this->assertDatabaseHas('course_registrations', ['user_id' => $user->id, 'rating' => 5]);
        $this->assertDatabaseHas('course_completions', ['user_id' => $user->id, 'rating' => 5]);
    }

    public function test_user_cannot_submit_rating_without_completing_first(): void
    {
        Event::fake();
        $user         = User::factory()->create(['role' => 'user']);
        $token        = $this->userToken($user);
        $course       = $this->createPublicCourse();
        $availability = $this->createAvailability($course);

        CourseRegistration::query()->create([
            'user_id'                => $user->id,
            'course_id'              => $course->id,
            'course_availability_id' => $availability->id,
            'status'                 => 'in_progress',
            'registered_at'          => now(),
        ]);

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/user/courses/submitRating/' . $course->id, [
                'rating' => 4,
            ])->assertUnprocessable();
    }

    public function test_user_can_view_own_enrollments(): void
    {
        Event::fake();
        $user         = User::factory()->create(['role' => 'user']);
        $token        = $this->userToken($user);
        $course       = $this->createPublicCourse();
        $availability = $this->createAvailability($course);

        CourseRegistration::query()->create([
            'user_id'                => $user->id,
            'course_id'              => $course->id,
            'course_availability_id' => $availability->id,
            'status'                 => 'in_progress',
            'registered_at'          => now(),
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/user/courses/my-enrollments');

        $response->assertOk();
        $this->assertCount(1, $response->json('data'));
    }
}
