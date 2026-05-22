<?php

namespace Tests\Feature;

use App\Models\CourseModule;
use App\Models\CourseOnline;
use App\Models\CourseOnlineAssignment;
use App\Models\LearningSession;
use App\Models\ModuleContent;
use App\Models\User;
use App\Models\UserContentProgress;
use App\Models\UserCourseProgress;
use App\Models\Video;
use App\Models\VideoCategory;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class LearningSessionApiTest extends TestCase
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
            'name'       => 'Online Course',
            'status'     => 'published',
            'is_active'  => true,
            'created_by' => $admin->id,
        ]);
    }

    private function createModule(CourseOnline $course, array $attrs = []): CourseModule
    {
        return CourseModule::create(array_merge([
            'course_online_id' => $course->id,
            'name'             => 'Module',
            'order_number'     => 1,
            'has_quiz'         => false,
            'quiz_required'    => false,
        ], $attrs));
    }

    private function createContent(CourseModule $module, array $attrs = []): ModuleContent
    {
        return ModuleContent::create(array_merge([
            'module_id'    => $module->id,
            'title'        => 'Content',
            'content_type' => 'video',
            'order_number' => 1,
            'is_required'  => true,
            'is_active'    => true,
            'duration'     => 300,
        ], $attrs));
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

    private function startSessionPayload(CourseOnline $course, ModuleContent $content): array
    {
        return [
            'course_online_id' => $course->id,
            'content_id'       => $content->id,
            'content_type'     => 'video',
        ];
    }

    private function progressPayload(array $overrides = []): array
    {
        return array_merge([
            'active_playback_time'  => 60,
            'playback_position'     => 60.0,
            'completion_percentage' => 20.0,
            'skip_count'            => 0,
            'seek_count'            => 0,
            'replay_count'          => 0,
            'pause_count'           => 1,
            'speed_changes'         => 0,
        ], $overrides);
    }

    private function endPayload(array $overrides = []): array
    {
        return array_merge([
            'active_playback_time'  => 200,
            'wall_clock_time'       => 250,
            'playback_position'     => 200.0,
            'completion_percentage' => 70.0,
            'skip_count'            => 0,
            'seek_count'            => 0,
            'replay_count'          => 0,
            'pause_count'           => 1,
            'speed_changes'         => 0,
            'fullscreen_count'      => 0,
        ], $overrides);
    }

    // -------------------------------------------------------------------------
    // 6.10.8
    // -------------------------------------------------------------------------

    public function test_start_session_creates_row_and_returns_session_id(): void
    {
        $user    = $this->createUser();
        $course  = $this->createCourse();
        $module  = $this->createModule($course);
        $content = $this->createContent($module);
        $this->assignUserToCourse($user, $course);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/user/online-courses/sessions/start', $this->startSessionPayload($course, $content));

        $response->assertOk();
        $this->assertNotNull($response->json('data.session_id'));
        $this->assertEquals(0, $response->json('data.resume_position'));

        $this->assertDatabaseHas('learning_sessions', [
            'user_id'          => $user->id,
            'course_online_id' => $course->id,
            'content_id'       => $content->id,
        ]);
    }

    // -------------------------------------------------------------------------
    // 6.10.9
    // -------------------------------------------------------------------------

    public function test_start_session_returns_resume_position_from_prior_progress(): void
    {
        $user    = $this->createUser();
        $course  = $this->createCourse();
        $module  = $this->createModule($course);
        $content = $this->createContent($module);
        $this->assignUserToCourse($user, $course);

        UserContentProgress::create([
            'user_id'               => $user->id,
            'content_id'            => $content->id,
            'course_online_id'      => $course->id,
            'module_id'             => $module->id,
            'content_type'          => 'video',
            'playback_position'     => 134.5,
            'completion_percentage' => 45.0,
            'is_completed'          => false,
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/user/online-courses/sessions/start', $this->startSessionPayload($course, $content));

        $response->assertOk();
        $this->assertEquals(134.5, $response->json('data.resume_position'));
    }

    // -------------------------------------------------------------------------
    // 6.10.10
    // -------------------------------------------------------------------------

    public function test_start_session_returns_403_for_completed_content(): void
    {
        $user    = $this->createUser();
        $course  = $this->createCourse();
        $module  = $this->createModule($course);
        $content = $this->createContent($module);
        $this->assignUserToCourse($user, $course);

        UserContentProgress::create([
            'user_id'               => $user->id,
            'content_id'            => $content->id,
            'course_online_id'      => $course->id,
            'module_id'             => $module->id,
            'content_type'          => 'video',
            'completion_percentage' => 100,
            'is_completed'          => true,
            'completed_at'          => now(),
        ]);

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/user/online-courses/sessions/start', $this->startSessionPayload($course, $content))
            ->assertForbidden();
    }

    // -------------------------------------------------------------------------
    // 6.10.11
    // -------------------------------------------------------------------------

    public function test_start_session_auto_closes_ghost_session(): void
    {
        $user    = $this->createUser();
        $course  = $this->createCourse();
        $module  = $this->createModule($course);
        $content = $this->createContent($module);
        $this->assignUserToCourse($user, $course);

        // Create a ghost session (last_progress_at = 15 minutes ago)
        $ghost = LearningSession::create([
            'user_id'              => $user->id,
            'course_online_id'     => $course->id,
            'content_id'           => $content->id,
            'content_type'         => 'video',
            'session_start'        => now()->subMinutes(20),
            'last_progress_at'     => now()->subMinutes(15),
            'active_playback_time' => 100,
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/user/online-courses/sessions/start', $this->startSessionPayload($course, $content));

        $response->assertOk();

        // Ghost session should be closed
        $this->assertNotNull($ghost->fresh()->session_end);

        // A new session was created
        $newSessionId = $response->json('data.session_id');
        $this->assertNotEquals($ghost->id, $newSessionId);
    }

    // -------------------------------------------------------------------------
    // 6.10.12
    // -------------------------------------------------------------------------

    public function test_start_session_returns_existing_if_within_5_minutes(): void
    {
        $user    = $this->createUser();
        $course  = $this->createCourse();
        $module  = $this->createModule($course);
        $content = $this->createContent($module);
        $this->assignUserToCourse($user, $course);

        $first = $this->actingAs($user, 'sanctum')
            ->postJson('/api/user/online-courses/sessions/start', $this->startSessionPayload($course, $content));

        $first->assertOk();
        $firstSessionId = $first->json('data.session_id');

        // Update last_progress_at to just now (within 5 min)
        LearningSession::find($firstSessionId)->update(['last_progress_at' => now()]);

        $second = $this->actingAs($user, 'sanctum')
            ->postJson('/api/user/online-courses/sessions/start', $this->startSessionPayload($course, $content));

        $second->assertOk();
        $this->assertEquals($firstSessionId, $second->json('data.session_id'));

        // Exactly one session row in DB
        $this->assertDatabaseCount('learning_sessions', 1);
    }

    // -------------------------------------------------------------------------
    // 6.10.13
    // -------------------------------------------------------------------------

    public function test_progress_update_does_not_decrease_completion(): void
    {
        $user    = $this->createUser();
        $course  = $this->createCourse();
        $module  = $this->createModule($course);
        $content = $this->createContent($module);
        $this->assignUserToCourse($user, $course);

        // Start session
        $sessionId = $this->actingAs($user, 'sanctum')
            ->postJson('/api/user/online-courses/sessions/start', $this->startSessionPayload($course, $content))
            ->json('data.session_id');

        // Send progress with 60%
        $this->actingAs($user, 'sanctum')
            ->postJson("/api/user/online-courses/sessions/{$sessionId}/progress",
                $this->progressPayload(['completion_percentage' => 60.0]))
            ->assertOk();

        // Send lower progress (30%)
        $this->actingAs($user, 'sanctum')
            ->postJson("/api/user/online-courses/sessions/{$sessionId}/progress",
                $this->progressPayload(['completion_percentage' => 30.0]))
            ->assertOk();

        // completion should still be 60%
        $progress = UserContentProgress::where('user_id', $user->id)
            ->where('content_id', $content->id)
            ->first();

        $this->assertNotNull($progress);
        $this->assertEquals(60.0, (float) $progress->completion_percentage);
    }

    // -------------------------------------------------------------------------
    // 6.10.14
    // -------------------------------------------------------------------------

    public function test_progress_update_on_closed_session_returns_ok_silently(): void
    {
        $user    = $this->createUser();
        $course  = $this->createCourse();
        $module  = $this->createModule($course);
        $content = $this->createContent($module);
        $this->assignUserToCourse($user, $course);

        $sessionId = $this->actingAs($user, 'sanctum')
            ->postJson('/api/user/online-courses/sessions/start', $this->startSessionPayload($course, $content))
            ->json('data.session_id');

        // Close the session
        LearningSession::find($sessionId)->update(['session_end' => now()]);

        $this->actingAs($user, 'sanctum')
            ->postJson("/api/user/online-courses/sessions/{$sessionId}/progress", $this->progressPayload())
            ->assertOk()
            ->assertJson(['ok' => true]);
    }

    // -------------------------------------------------------------------------
    // 6.10.15
    // -------------------------------------------------------------------------

    public function test_end_session_calculates_attention_score_and_closes_session(): void
    {
        $user    = $this->createUser();
        $course  = $this->createCourse();
        $module  = $this->createModule($course);
        $content = $this->createContent($module, ['duration' => 300]);
        $this->assignUserToCourse($user, $course);

        $sessionId = $this->actingAs($user, 'sanctum')
            ->postJson('/api/user/online-courses/sessions/start', $this->startSessionPayload($course, $content))
            ->json('data.session_id');

        $response = $this->actingAs($user, 'sanctum')
            ->postJson("/api/user/online-courses/sessions/{$sessionId}/end", $this->endPayload([
                'active_playback_time'  => 240,
                'wall_clock_time'       => 250,
                'completion_percentage' => 80.0,
            ]));

        $response->assertOk();
        $this->assertNotNull($response->json('data.attention_score'));

        // session_end must be set
        $session = LearningSession::find($sessionId);
        $this->assertNotNull($session->session_end);

        // is_suspicious must NOT appear in user response
        $this->assertArrayNotHasKey('is_suspicious', $response->json('data'));

        // Fact row must be written
        $this->assertDatabaseHas('reporting_learning_sessions_fact', [
            'session_id' => $sessionId,
            'user_id'    => $user->id,
        ]);
    }

    // -------------------------------------------------------------------------
    // 6.10.16
    // -------------------------------------------------------------------------

    public function test_end_session_marks_content_completed_at_95_percent(): void
    {
        $user    = $this->createUser();
        $course  = $this->createCourse();
        $module  = $this->createModule($course);
        $content = $this->createContent($module);
        $this->assignUserToCourse($user, $course);

        $sessionId = $this->actingAs($user, 'sanctum')
            ->postJson('/api/user/online-courses/sessions/start', $this->startSessionPayload($course, $content))
            ->json('data.session_id');

        $this->actingAs($user, 'sanctum')
            ->postJson("/api/user/online-courses/sessions/{$sessionId}/end", $this->endPayload([
                'completion_percentage' => 96.0,
            ]))
            ->assertOk();

        $progress = UserContentProgress::where('user_id', $user->id)
            ->where('content_id', $content->id)
            ->first();

        $this->assertTrue((bool) $progress->is_completed);
        $this->assertNotNull($progress->completed_at);
    }

    // -------------------------------------------------------------------------
    // 6.10.17
    // -------------------------------------------------------------------------

    public function test_end_session_is_idempotent(): void
    {
        $user    = $this->createUser();
        $course  = $this->createCourse();
        $module  = $this->createModule($course);
        $content = $this->createContent($module);
        $this->assignUserToCourse($user, $course);

        $sessionId = $this->actingAs($user, 'sanctum')
            ->postJson('/api/user/online-courses/sessions/start', $this->startSessionPayload($course, $content))
            ->json('data.session_id');

        $payload = $this->endPayload(['completion_percentage' => 80.0]);

        // First call
        $first = $this->actingAs($user, 'sanctum')
            ->postJson("/api/user/online-courses/sessions/{$sessionId}/end", $payload);
        $first->assertOk();

        // Second call — idempotent
        $second = $this->actingAs($user, 'sanctum')
            ->postJson("/api/user/online-courses/sessions/{$sessionId}/end", $payload);
        $second->assertOk();

        $this->assertEquals(
            $first->json('data.attention_score'),
            $second->json('data.attention_score')
        );

        // Only one fact row
        $this->assertDatabaseCount('reporting_learning_sessions_fact', 1);
    }

    // -------------------------------------------------------------------------
    // 6.10.18
    // -------------------------------------------------------------------------

    public function test_end_session_triggers_course_progress_recalculation(): void
    {
        $user    = $this->createUser();
        $course  = $this->createCourse();
        $module  = $this->createModule($course);
        $content1 = $this->createContent($module, ['order_number' => 1]);
        $content2 = $this->createContent($module, ['title' => 'Content 2', 'order_number' => 2]);
        $this->assignUserToCourse($user, $course);

        // Complete content1 manually (marks it 100%)
        UserContentProgress::create([
            'user_id'               => $user->id,
            'content_id'            => $content1->id,
            'course_online_id'      => $course->id,
            'module_id'             => $module->id,
            'content_type'          => 'video',
            'completion_percentage' => 100,
            'is_completed'          => true,
            'completed_at'          => now(),
        ]);

        // Start + end session for content2 at 96% (should complete it)
        $sessionId = $this->actingAs($user, 'sanctum')
            ->postJson('/api/user/online-courses/sessions/start', [
                'course_online_id' => $course->id,
                'content_id'       => $content2->id,
                'content_type'     => 'video',
            ])
            ->json('data.session_id');

        $this->actingAs($user, 'sanctum')
            ->postJson("/api/user/online-courses/sessions/{$sessionId}/end", $this->endPayload([
                'completion_percentage' => 96.0,
            ]))
            ->assertOk();

        // Both content items completed → course should be completed
        $courseProgress = UserCourseProgress::where('user_id', $user->id)
            ->where('course_online_id', $course->id)
            ->first();

        $this->assertNotNull($courseProgress);
        $this->assertEquals('completed', $courseProgress->status);
        $this->assertEquals(100.0, (float) $courseProgress->progress_percentage);
    }

    // -------------------------------------------------------------------------
    // 6.10.19
    // -------------------------------------------------------------------------

    public function test_session_user_mismatch_returns_403(): void
    {
        $userA   = $this->createUser();
        $userB   = $this->createUser();
        $course  = $this->createCourse();
        $module  = $this->createModule($course);
        $content = $this->createContent($module);
        $this->assignUserToCourse($userA, $course);
        $this->assignUserToCourse($userB, $course);

        // UserA starts session
        $sessionId = $this->actingAs($userA, 'sanctum')
            ->postJson('/api/user/online-courses/sessions/start', $this->startSessionPayload($course, $content))
            ->json('data.session_id');

        // UserB tries to send progress on UserA's session
        $this->actingAs($userB, 'sanctum')
            ->postJson("/api/user/online-courses/sessions/{$sessionId}/progress", $this->progressPayload())
            ->assertForbidden();
    }
}
