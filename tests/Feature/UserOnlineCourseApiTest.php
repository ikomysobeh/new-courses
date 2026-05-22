<?php

namespace Tests\Feature;

use App\Models\CourseModule;
use App\Models\CourseOnline;
use App\Models\CourseOnlineAssignment;
use App\Models\ModuleContent;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\User;
use App\Models\UserContentProgress;
use App\Models\UserCourseProgress;
use App\Models\Video;
use App\Models\VideoCategory;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserOnlineCourseApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function createUser(): User
    {
        return User::factory()->create(['role' => 'user']);
    }

    private function createCourse(array $attrs = []): CourseOnline
    {
        $admin = User::where('role', 'admin')->first();

        return CourseOnline::create(array_merge([
            'name'       => 'Test Online Course',
            'status'     => 'published',
            'is_active'  => true,
            'created_by' => $admin->id,
        ], $attrs));
    }

    private function createModule(CourseOnline $course, array $attrs = []): CourseModule
    {
        return CourseModule::create(array_merge([
            'course_online_id' => $course->id,
            'name'             => 'Test Module',
            'order_number'     => 1,
            'has_quiz'         => false,
            'quiz_required'    => false,
        ], $attrs));
    }

    private function createVideo(): Video
    {
        $cat = VideoCategory::create(['name' => 'Cat', 'slug' => 'cat-' . uniqid()]);

        return Video::create([
            'name'              => 'Test Video',
            'file_path'         => 'videos/test.mp4',
            'video_category_id' => $cat->id,
            'transcode_status'  => 'completed',
            'created_by'        => User::where('role', 'admin')->first()->id,
        ]);
    }

    private function createContent(CourseModule $module, array $attrs = []): ModuleContent
    {
        return ModuleContent::create(array_merge([
            'module_id'    => $module->id,
            'title'        => 'Test Content',
            'content_type' => 'video',
            'order_number' => 1,
            'is_required'  => true,
            'is_active'    => true,
            'duration'     => 300,
        ], $attrs));
    }

    private function assignUserToCourse(User $user, CourseOnline $course): CourseOnlineAssignment
    {
        return CourseOnlineAssignment::create([
            'user_id'          => $user->id,
            'course_online_id' => $course->id,
            'assigned_by'      => User::where('role', 'admin')->first()->id,
            'assigned_at'      => now(),
        ]);
    }

    private function markContentCompleted(User $user, ModuleContent $content, CourseOnline $course): void
    {
        UserContentProgress::updateOrCreate(
            ['user_id' => $user->id, 'content_id' => $content->id],
            [
                'course_online_id'      => $course->id,
                'module_id'             => $content->module_id,
                'content_type'          => $content->content_type,
                'completion_percentage' => 100,
                'is_completed'          => true,
                'completed_at'          => now(),
                'last_accessed_at'      => now(),
            ]
        );
    }

    // -------------------------------------------------------------------------
    // 6.10.1
    // -------------------------------------------------------------------------

    public function test_user_can_get_assigned_courses_with_progress(): void
    {
        $user    = $this->createUser();
        $course1 = $this->createCourse(['name' => 'Course A']);
        $course2 = $this->createCourse(['name' => 'Course B']);
        $module1 = $this->createModule($course1);
        $content1 = $this->createContent($module1);
        $this->assignUserToCourse($user, $course1);
        $this->assignUserToCourse($user, $course2);

        // Create progress on course1
        UserCourseProgress::create([
            'user_id'                 => $user->id,
            'course_online_id'        => $course1->id,
            'progress_percentage'     => 50,
            'status'                  => 'in_progress',
            'total_content_items'     => 1,
            'completed_content_items' => 0,
            'started_at'              => now(),
            'last_accessed_at'        => now(),
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/user/online-courses/getAll');

        $response->assertOk();
        $data = $response->json('data');
        $this->assertCount(2, $data);

        // Find course1 in result
        $c1 = collect($data)->firstWhere('title', 'Course A');
        $this->assertNotNull($c1);
        $this->assertEquals('in_progress', $c1['status']);
        $this->assertEquals(50, $c1['progress_percentage']);
    }

    // -------------------------------------------------------------------------
    // 6.10.2
    // -------------------------------------------------------------------------

    public function test_user_cannot_see_unassigned_course(): void
    {
        $user         = $this->createUser();
        $assignedCourse   = $this->createCourse(['name' => 'Assigned']);
        $unassignedCourse = $this->createCourse(['name' => 'Unassigned']);
        $this->assignUserToCourse($user, $assignedCourse);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/user/online-courses/getAll');

        $response->assertOk();
        $titles = collect($response->json('data'))->pluck('title')->toArray();
        $this->assertContains('Assigned', $titles);
        $this->assertNotContains('Unassigned', $titles);
    }

    // -------------------------------------------------------------------------
    // 6.10.3
    // -------------------------------------------------------------------------

    public function test_user_can_get_course_detail_with_modules(): void
    {
        $user    = $this->createUser();
        $course  = $this->createCourse();
        $module1 = $this->createModule($course, ['order_number' => 1]);
        $module2 = $this->createModule($course, ['name' => 'Module 2', 'order_number' => 2]);
        $this->createContent($module1, ['title' => 'Video 1']);
        $this->createContent($module2, ['title' => 'Video 2']);
        $this->assignUserToCourse($user, $course);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson("/api/user/online-courses/getById/{$course->id}");

        $response->assertOk();
        $modules = $response->json('data.modules');
        $this->assertCount(2, $modules);
        $this->assertArrayHasKey('is_unlocked', $modules[0]);
        $this->assertArrayHasKey('is_completed', $modules[0]);
        $this->assertNotEmpty($modules[0]['content']);
        $this->assertArrayHasKey('progress', $modules[0]['content'][0]);
    }

    // -------------------------------------------------------------------------
    // 6.10.4
    // -------------------------------------------------------------------------

    public function test_first_module_is_always_unlocked(): void
    {
        $user   = $this->createUser();
        $course = $this->createCourse();
        $this->createModule($course, ['order_number' => 1]);
        $this->assignUserToCourse($user, $course);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson("/api/user/online-courses/getById/{$course->id}");

        $response->assertOk();
        $this->assertTrue($response->json('data.modules.0.is_unlocked'));
    }

    // -------------------------------------------------------------------------
    // 6.10.5
    // -------------------------------------------------------------------------

    public function test_second_module_locked_until_first_complete(): void
    {
        $user    = $this->createUser();
        $course  = $this->createCourse();
        $module1 = $this->createModule($course, ['order_number' => 1]);
        $module2 = $this->createModule($course, ['name' => 'Module 2', 'order_number' => 2]);
        $content1 = $this->createContent($module1, ['is_required' => true]);
        $this->createContent($module2, ['title' => 'Content 2']);
        $this->assignUserToCourse($user, $course);

        // No progress — module 2 should be locked
        $response = $this->actingAs($user, 'sanctum')
            ->getJson("/api/user/online-courses/getById/{$course->id}");

        $response->assertOk();
        $this->assertFalse($response->json('data.modules.1.is_unlocked'));

        // Complete module 1's required content
        $this->markContentCompleted($user, $content1, $course);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson("/api/user/online-courses/getById/{$course->id}");

        $response->assertOk();
        $this->assertTrue($response->json('data.modules.1.is_unlocked'));
    }

    // -------------------------------------------------------------------------
    // 6.10.6
    // -------------------------------------------------------------------------

    public function test_module_with_required_quiz_locked_until_passed(): void
    {
        $user    = $this->createUser();
        $course  = $this->createCourse();
        $module1 = $this->createModule($course, [
            'order_number' => 1,
            'has_quiz'     => true,
            'quiz_required' => true,
        ]);
        $module2 = $this->createModule($course, ['name' => 'Module 2', 'order_number' => 2]);
        $content1 = $this->createContent($module1, ['is_required' => true]);
        $this->createContent($module2, ['title' => 'Content 2']);

        $quiz = Quiz::create([
            'module_id'       => $module1->id,
            'title'           => 'Module Quiz',
            'status'          => 'published',
            'pass_threshold'  => 80,
            'total_points'    => 10,
        ]);

        $this->assignUserToCourse($user, $course);

        // Content done but quiz not passed → module 2 locked
        $this->markContentCompleted($user, $content1, $course);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson("/api/user/online-courses/getById/{$course->id}");

        $response->assertOk();
        $this->assertFalse($response->json('data.modules.1.is_unlocked'));

        // Add passing quiz attempt → module 2 unlocked
        QuizAttempt::create([
            'quiz_id'        => $quiz->id,
            'user_id'        => $user->id,
            'attempt_number' => 1,
            'started_at'     => now()->subMinutes(5),
            'completed_at'   => now(),
            'score'          => 9,
            'total_score'    => 10,
            'passed'         => true,
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson("/api/user/online-courses/getById/{$course->id}");

        $response->assertOk();
        $this->assertTrue($response->json('data.modules.1.is_unlocked'));
    }

    // -------------------------------------------------------------------------
    // 6.10.7
    // -------------------------------------------------------------------------

    public function test_user_cannot_access_unassigned_course(): void
    {
        $user  = $this->createUser();
        $other = $this->createCourse(['name' => 'Other Course']);

        $this->actingAs($user, 'sanctum')
            ->getJson("/api/user/online-courses/getById/{$other->id}")
            ->assertForbidden();
    }

    // -------------------------------------------------------------------------
    // 6.10.26 (Security)
    // -------------------------------------------------------------------------

    public function test_user_not_assigned_to_course_gets_403_on_detail(): void
    {
        $userA = $this->createUser();
        $userB = $this->createUser();
        $course = $this->createCourse();
        $this->assignUserToCourse($userA, $course);

        $this->actingAs($userB, 'sanctum')
            ->getJson("/api/user/online-courses/getById/{$course->id}")
            ->assertForbidden();
    }
}
