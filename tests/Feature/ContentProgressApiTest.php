<?php

namespace Tests\Feature;

use App\Models\CourseModule;
use App\Models\CourseOnline;
use App\Models\CourseOnlineAssignment;
use App\Models\LearningSession;
use App\Models\ModuleContent;
use App\Models\ModuleContentPdf;
use App\Models\User;
use App\Models\UserContentProgress;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class ContentProgressApiTest extends TestCase
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

    private function createCourse(): CourseOnline
    {
        $admin = User::where('role', 'admin')->first();

        return CourseOnline::create([
            'name'       => 'PDF Course',
            'status'     => 'published',
            'is_active'  => true,
            'created_by' => $admin->id,
        ]);
    }

    private function createModule(CourseOnline $course): CourseModule
    {
        return CourseModule::create([
            'course_online_id' => $course->id,
            'name'             => 'Module',
            'order_number'     => 1,
            'has_quiz'         => false,
            'quiz_required'    => false,
        ]);
    }

    private function createPdfContent(CourseModule $module, int $pageCount = 10): ModuleContent
    {
        $content = ModuleContent::create([
            'module_id'    => $module->id,
            'title'        => 'PDF Content',
            'content_type' => 'pdf',
            'order_number' => 1,
            'is_required'  => true,
            'is_active'    => true,
            'duration'     => 0,
        ]);

        ModuleContentPdf::create([
            'module_content_id' => $content->id,
            'file_path'         => 'course-pdfs/test.pdf',
            'pdf_page_count'    => $pageCount,
        ]);

        return $content;
    }

    private function assignUserToCourse(User $user, CourseOnline $course): void
    {
        CourseOnlineAssignment::create([
            'user_id'          => $user->id,
            'course_online_id' => $course->id,
            'assigned_by'      => User::where('role', 'admin')->first()->id,
            'assigned_at'      => now(),
        ]);
    }

    // -------------------------------------------------------------------------
    // 6.10.20
    // -------------------------------------------------------------------------

    public function test_pdf_progress_updates_on_page_change(): void
    {
        $user    = $this->createUser();
        $course  = $this->createCourse();
        $module  = $this->createModule($course);
        $content = $this->createPdfContent($module, 10);
        $this->assignUserToCourse($user, $course);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/user/online-courses/progress/pdf', [
                'content_id'       => $content->id,
                'course_online_id' => $course->id,
                'pages_viewed'     => 5,
                'total_pages'      => 10,
                'current_page'     => 5,
            ]);

        $response->assertOk();
        $this->assertEquals(50.0, $response->json('completion_percentage'));

        $progress = UserContentProgress::where('user_id', $user->id)
            ->where('content_id', $content->id)
            ->first();

        $this->assertNotNull($progress);
        $this->assertEquals(5, (int) $progress->playback_position);
        $this->assertFalse((bool) $progress->is_completed);
    }

    // -------------------------------------------------------------------------
    // 6.10.21
    // -------------------------------------------------------------------------

    public function test_pdf_progress_marks_completed_when_all_pages_viewed(): void
    {
        $user    = $this->createUser();
        $course  = $this->createCourse();
        $module  = $this->createModule($course);
        $content = $this->createPdfContent($module, 10);
        $this->assignUserToCourse($user, $course);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/user/online-courses/progress/pdf', [
                'content_id'       => $content->id,
                'course_online_id' => $course->id,
                'pages_viewed'     => 10,
                'total_pages'      => 10,
                'current_page'     => 10,
            ]);

        $response->assertOk();
        $this->assertTrue($response->json('is_completed'));

        $progress = UserContentProgress::where('user_id', $user->id)
            ->where('content_id', $content->id)
            ->first();

        $this->assertTrue((bool) $progress->is_completed);
        $this->assertNotNull($progress->completed_at);
    }

    // (test_pdf_progress_marks_completed_when_all_pages_viewed inlined above)

    // -------------------------------------------------------------------------
    // 6.10.22
    // -------------------------------------------------------------------------

    public function test_pdf_pages_viewed_never_decreases(): void
    {
        $user    = $this->createUser();
        $course  = $this->createCourse();
        $module  = $this->createModule($course);
        $content = $this->createPdfContent($module, 10);
        $this->assignUserToCourse($user, $course);

        $base = [
            'content_id'       => $content->id,
            'course_online_id' => $course->id,
            'total_pages'      => 10,
            'current_page'     => 8,
        ];

        // Send 8 pages viewed
        $this->actingAs($user, 'sanctum')
            ->postJson('/api/user/online-courses/progress/pdf', array_merge($base, ['pages_viewed' => 8, 'current_page' => 8]))
            ->assertOk();

        // Send lower value (3 pages)
        $this->actingAs($user, 'sanctum')
            ->postJson('/api/user/online-courses/progress/pdf', array_merge($base, ['pages_viewed' => 3, 'current_page' => 3]))
            ->assertOk();

        $progress = UserContentProgress::where('user_id', $user->id)
            ->where('content_id', $content->id)
            ->first();

        // Should still be 8
        $this->assertEquals(8, (int) $progress->pdf_pages_viewed);
    }

    // -------------------------------------------------------------------------
    // 6.10.23
    // -------------------------------------------------------------------------

    public function test_resume_position_returns_zero_for_new_user(): void
    {
        $user    = $this->createUser();
        $course  = $this->createCourse();
        $module  = $this->createModule($course);
        $content = $this->createPdfContent($module);
        $this->assignUserToCourse($user, $course);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson("/api/user/online-courses/progress/{$content->id}/resume");

        $response->assertOk();
        $this->assertEquals(0, $response->json('playback_position'));
        $this->assertFalse($response->json('is_completed'));
        $this->assertNull($response->json('last_accessed_at'));
    }

    // -------------------------------------------------------------------------
    // 6.10.24
    // -------------------------------------------------------------------------

    public function test_resume_position_returns_stored_position(): void
    {
        $user    = $this->createUser();
        $course  = $this->createCourse();
        $module  = $this->createModule($course);
        $content = $this->createPdfContent($module);
        $this->assignUserToCourse($user, $course);

        UserContentProgress::create([
            'user_id'               => $user->id,
            'content_id'            => $content->id,
            'course_online_id'      => $course->id,
            'module_id'             => $module->id,
            'content_type'          => 'pdf',
            'playback_position'     => 222.0,
            'completion_percentage' => 55.0,
            'is_completed'          => false,
            'last_accessed_at'      => now(),
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson("/api/user/online-courses/progress/{$content->id}/resume");

        $response->assertOk();
        $this->assertEquals(222.0, $response->json('playback_position'));
        $this->assertEquals(55.0, $response->json('completion_percentage'));
    }

    // -------------------------------------------------------------------------
    // 6.10.25 — Unauthenticated requests return 401
    // -------------------------------------------------------------------------

    public function test_unauthenticated_request_returns_401(): void
    {
        $this->getJson('/api/user/online-courses/getAll')->assertUnauthorized();
        $this->postJson('/api/user/online-courses/sessions/start')->assertUnauthorized();
        $this->postJson('/api/user/online-courses/progress/pdf')->assertUnauthorized();
    }

    // -------------------------------------------------------------------------
    // 6.10.27 — Expired signed URL returns 403
    // -------------------------------------------------------------------------

    public function test_expired_signed_media_url_returns_403(): void
    {
        $course  = $this->createCourse();
        $module  = $this->createModule($course);

        // Generate a signed URL with 4-hour expiry, then travel 5 hours forward
        $signedUrl = URL::temporarySignedRoute('media.video', now()->addHours(4), ['content_id' => 999]);

        $this->travelTo(now()->addHours(5));

        $this->get($signedUrl)->assertStatus(403);
    }

    // -------------------------------------------------------------------------
    // 6.10.28 — Forged (tampered) signed URL returns 403
    // -------------------------------------------------------------------------

    public function test_forged_signed_media_url_returns_403(): void
    {
        // Generate a valid signed URL for content_id = 1
        $signedUrl = URL::temporarySignedRoute('media.video', now()->addHours(4), ['content_id' => 1]);

        // Change the content_id path segment — this invalidates the signature
        $forgedUrl = preg_replace('#/media/video/[^?]+#', '/media/video/9999', $signedUrl);

        $this->get($forgedUrl)->assertStatus(403);
    }

    // -------------------------------------------------------------------------
    // 6.10.29 — is_suspicious is NOT in user-facing end-session response
    // -------------------------------------------------------------------------

    public function test_is_suspicious_not_exposed_in_end_session_response(): void
    {
        $user    = $this->createUser();
        $course  = $this->createCourse();
        $module  = $this->createModule($course);
        $content = ModuleContent::create([
            'module_id'    => $module->id,
            'title'        => 'Video Content',
            'content_type' => 'video',
            'order_number' => 1,
            'is_required'  => true,
            'is_active'    => true,
            'duration'     => 300,
        ]);
        $this->assignUserToCourse($user, $course);

        $sessionId = $this->actingAs($user, 'sanctum')
            ->postJson('/api/user/online-courses/sessions/start', [
                'course_online_id' => $course->id,
                'content_id'       => $content->id,
                'content_type'     => 'video',
            ])
            ->json('data.session_id');

        $response = $this->actingAs($user, 'sanctum')
            ->postJson("/api/user/online-courses/sessions/{$sessionId}/end", [
                'active_playback_time'  => 200,
                'wall_clock_time'       => 250,
                'playback_position'     => 200.0,
                'completion_percentage' => 80.0,
                'skip_count'            => 0,
                'seek_count'            => 0,
                'replay_count'          => 0,
                'pause_count'           => 0,
                'speed_changes'         => 0,
                'fullscreen_count'      => 0,
            ]);

        $response->assertOk();

        // is_suspicious must NOT be in the response
        $this->assertArrayNotHasKey('is_suspicious', $response->json('data'));
    }
}
