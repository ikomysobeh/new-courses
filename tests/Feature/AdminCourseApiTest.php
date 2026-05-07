<?php

namespace Tests\Feature;

use App\Events\CourseAssigned;
use App\Events\PrivacyChangedToPublic;
use App\Events\PublicCourseCreated;
use App\Models\Course;
use App\Models\CourseAvailability;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class AdminCourseApiTest extends TestCase
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

    private function availabilityPayload(array $overrides = []): array
    {
        return array_merge([
            'start_date' => now()->addDays(5)->toDateTimeString(),
            'end_date'   => now()->addDays(30)->toDateTimeString(),
            'capacity'   => 20,
            'sessions'   => 20,
        ], $overrides);
    }

    public function test_admin_can_get_all_courses_paginated(): void
    {
        $token = $this->adminToken();

        $admin = User::query()->where('role', 'admin')->first();
        Course::factory()->count(3)->create(['created_by' => $admin->id]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/admin/courses/getAll');

        $response->assertOk()->assertJsonStructure([
            'data',
            'meta',
            'cards' => [
                '*' => ['key', 'title', 'value'],
            ],
        ]);
        $response->assertJsonPath('cards.0.key', 'total_courses');
    }

    public function test_admin_can_create_course_with_one_availability(): void
    {
        Event::fake();
        $token = $this->adminToken();

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/admin/courses/create', [
                'name'           => 'Laravel Fundamentals',
                'status'         => 'published',
                'privacy'        => 'public',
                'availabilities' => [$this->availabilityPayload()],
            ]);

        $response->assertCreated()->assertJsonPath('data.name', 'Laravel Fundamentals');
        $this->assertDatabaseHas('courses', ['name' => 'Laravel Fundamentals']);
        $this->assertDatabaseCount('course_availabilities', 1);

        Event::assertDispatched(PublicCourseCreated::class);
    }

    public function test_admin_can_create_course_with_up_to_five_availabilities(): void
    {
        Event::fake();
        $token = $this->adminToken();

        $availabilities = array_fill(0, 5, $this->availabilityPayload());

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/admin/courses/create', [
                'name'           => 'Multi-Session Course',
                'status'         => 'draft',
                'privacy'        => 'private',
                'availabilities' => $availabilities,
            ]);

        $response->assertCreated();
        $this->assertDatabaseCount('course_availabilities', 5);
    }

    public function test_admin_can_get_course_by_id_with_availabilities(): void
    {
        $token = $this->adminToken();
        $admin = User::query()->where('role', 'admin')->first();

        $course = Course::query()->create([
            'name'       => 'Detail Test Course',
            'status'     => 'draft',
            'privacy'    => 'public',
            'created_by' => $admin->id,
        ]);

        CourseAvailability::query()->create([
            'course_id'  => $course->id,
            'start_date' => now()->addDays(5),
            'end_date'   => now()->addDays(30),
            'capacity'   => 10,
            'sessions'   => 10,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/admin/courses/getById/' . $course->id);

        $response->assertOk()
            ->assertJsonPath('data.name', 'Detail Test Course')
            ->assertJsonStructure(['data' => ['availabilities']]);

        $this->assertCount(1, $response->json('data.availabilities'));
    }

    public function test_admin_can_update_course_and_add_new_availability(): void
    {
        Event::fake();
        $token = $this->adminToken();
        $admin = User::query()->where('role', 'admin')->first();

        $course = Course::query()->create([
            'name'       => 'Old Name',
            'status'     => 'draft',
            'privacy'    => 'private',
            'created_by' => $admin->id,
        ]);

        $existing = CourseAvailability::query()->create([
            'course_id'  => $course->id,
            'start_date' => now()->addDays(5),
            'end_date'   => now()->addDays(20),
            'capacity'   => 10,
            'sessions'   => 10,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->putJson('/api/admin/courses/update/' . $course->id, [
                'name'   => 'Updated Name',
                'availabilities' => [
                    ['id' => $existing->id, 'capacity' => 15],
                    $this->availabilityPayload(['capacity' => 5]),
                ],
            ]);

        $response->assertOk()->assertJsonPath('data.name', 'Updated Name');
        $this->assertDatabaseHas('courses', ['id' => $course->id, 'name' => 'Updated Name']);
        $this->assertDatabaseHas('course_availabilities', ['id' => $existing->id, 'capacity' => 15]);
        $this->assertDatabaseCount('course_availabilities', 2);
    }

    public function test_admin_update_auto_closes_omitted_availability_without_enrollments(): void
    {
        Event::fake();
        $token = $this->adminToken();
        $admin = User::query()->where('role', 'admin')->first();

        $course = Course::query()->create([
            'name'       => 'Course X',
            'status'     => 'draft',
            'privacy'    => 'private',
            'created_by' => $admin->id,
        ]);

        $omitted = CourseAvailability::query()->create([
            'course_id'  => $course->id,
            'start_date' => now()->addDays(5),
            'end_date'   => now()->addDays(20),
            'capacity'   => 10,
            'sessions'   => 10,
            'status'     => 'active',
        ]);

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->putJson('/api/admin/courses/update/' . $course->id, [
                'availabilities' => [
                    $this->availabilityPayload(),
                ],
            ]);

        $this->assertDatabaseHas('course_availabilities', ['id' => $omitted->id, 'status' => 'closed']);
    }

    public function test_admin_update_does_not_close_availability_with_enrollments(): void
    {
        Event::fake();
        $token = $this->adminToken();
        $admin = User::query()->where('role', 'admin')->first();
        $user  = User::factory()->create(['role' => 'user']);

        $course = Course::query()->create([
            'name'       => 'Course Y',
            'status'     => 'published',
            'privacy'    => 'public',
            'created_by' => $admin->id,
        ]);

        $avWithEnrollment = CourseAvailability::query()->create([
            'course_id'  => $course->id,
            'start_date' => now()->addDays(5),
            'end_date'   => now()->addDays(20),
            'capacity'   => 10,
            'sessions'   => 9,
            'status'     => 'active',
        ]);

        \App\Models\CourseRegistration::query()->create([
            'user_id'                => $user->id,
            'course_id'              => $course->id,
            'course_availability_id' => $avWithEnrollment->id,
            'status'                 => 'in_progress',
            'registered_at'          => now(),
        ]);

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->putJson('/api/admin/courses/update/' . $course->id, [
                'availabilities' => [
                    $this->availabilityPayload(),
                ],
            ]);

        $this->assertDatabaseHas('course_availabilities', ['id' => $avWithEnrollment->id, 'status' => 'active']);
    }

    public function test_admin_can_soft_delete_course(): void
    {
        Event::fake();
        $token = $this->adminToken();
        $admin = User::query()->where('role', 'admin')->first();

        $course = Course::query()->create([
            'name'       => 'Delete Me',
            'status'     => 'draft',
            'privacy'    => 'private',
            'created_by' => $admin->id,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->deleteJson('/api/admin/courses/delete/' . $course->id);

        $response->assertOk();
        $this->assertSoftDeleted('courses', ['id' => $course->id]);
    }

    public function test_privacy_change_to_public_dispatches_event(): void
    {
        Event::fake();
        $token = $this->adminToken();
        $admin = User::query()->where('role', 'admin')->first();

        $course = Course::query()->create([
            'name'       => 'Private Course',
            'status'     => 'published',
            'privacy'    => 'private',
            'created_by' => $admin->id,
        ]);

        CourseAvailability::query()->create([
            'course_id'  => $course->id,
            'start_date' => now()->addDays(5),
            'end_date'   => now()->addDays(20),
            'capacity'   => 10,
            'sessions'   => 10,
        ]);

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->putJson('/api/admin/courses/update/' . $course->id, [
                'privacy' => 'public',
            ]);

        Event::assertDispatched(PrivacyChangedToPublic::class);
        Event::assertNotDispatched(PublicCourseCreated::class);
    }

    public function test_non_admin_cannot_access_admin_course_endpoints(): void
    {
        $this->seed(DatabaseSeeder::class);
        $user  = User::factory()->create(['role' => 'user']);
        $token = (string) $this->postJson('/api/login', [
            'email'    => $user->email,
            'password' => 'password',
        ])->json('data.token');

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/admin/courses/getAll')
            ->assertForbidden();
    }
}
