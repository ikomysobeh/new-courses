<?php

namespace Tests\Feature;

use App\Models\CourseModule;
use App\Models\CourseOnline;
use App\Models\CourseOnlineAssignment;
use App\Models\ModuleContent;
use App\Models\ModuleContentPdf;
use App\Models\User;
use App\Models\Video;
use App\Models\VideoCategory;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminOnlineCourseApiTest extends TestCase
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

    private function createVideo(): Video
    {
        $category = VideoCategory::query()->create(['name' => 'Test Category', 'slug' => 'test-category']);

        return Video::query()->create([
            'name'              => 'Test Video',
            'file_path'         => 'videos/test.mp4',
            'video_category_id' => $category->id,
            'transcode_status'  => 'completed',
            'created_by'        => $this->admin()->id,
        ]);
    }

    // -------------------------------------------------------------------------
    // create
    // -------------------------------------------------------------------------

    public function test_admin_can_create_course_with_modules_and_content(): void
    {
        Storage::fake('local');
        $token = $this->adminToken();
        $video = $this->createVideo();

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/admin/online-courses/create', [
                'name'   => 'Full Course',
                'status' => 'draft',
                'modules' => [
                    [
                        'name'         => 'Module 1',
                        'order_number' => 1,
                        'contents'     => [
                            [
                                'title'        => 'Video Lesson 1',
                                'content_type' => 'video',
                                'order_number' => 1,
                                'video_id'     => $video->id,
                            ],
                            [
                                'title'        => 'PDF Lesson 1',
                                'content_type' => 'pdf',
                                'order_number' => 2,
                                'pdf'          => ['file_path' => 'course-pdfs/test1.pdf'],
                            ],
                        ],
                    ],
                    [
                        'name'         => 'Module 2',
                        'order_number' => 2,
                        'contents'     => [
                            [
                                'title'        => 'Video Lesson 2',
                                'content_type' => 'video',
                                'order_number' => 1,
                                'video_id'     => $video->id,
                            ],
                            [
                                'title'        => 'PDF Lesson 2',
                                'content_type' => 'pdf',
                                'order_number' => 2,
                                'pdf'          => ['file_path' => 'course-pdfs/test2.pdf'],
                            ],
                        ],
                    ],
                ],
            ]);

        $response->assertCreated();
        $this->assertDatabaseCount('course_modules', 2);
        $this->assertDatabaseCount('module_contents', 4);
        $this->assertDatabaseCount('module_content_pdfs', 2);
    }

    public function test_admin_can_create_minimal_course_no_modules(): void
    {
        $token = $this->adminToken();

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/admin/online-courses/create', [
                'name'   => 'Minimal Course',
                'status' => 'draft',
            ]);

        $response->assertCreated()
            ->assertJsonPath('data.name', 'Minimal Course');

        $this->assertDatabaseHas('course_onlines', ['name' => 'Minimal Course']);
        $this->assertDatabaseHas('course_analytics', ['course_online_id' => $response->json('data.id')]);
    }

    public function test_admin_can_get_course_by_id_with_full_tree(): void
    {
        Storage::fake('local');
        $token = $this->adminToken();
        $admin = $this->admin();
        $video = $this->createVideo();

        // Create via API so analytics row exists
        $createResp = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/admin/online-courses/create', [
                'name'    => 'Tree Course',
                'modules' => [
                    [
                        'name'         => 'Module A',
                        'order_number' => 1,
                        'contents'     => [
                            [
                                'title'        => 'PDF Content',
                                'content_type' => 'pdf',
                                'order_number' => 1,
                                'pdf'          => ['file_path' => 'course-pdfs/doc.pdf'],
                            ],
                        ],
                    ],
                ],
            ]);

        $courseId = $createResp->json('data.id');

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson("/api/admin/online-courses/getById/{$courseId}");

        $response->assertOk()
            ->assertJsonPath('data.id', $courseId)
            ->assertJsonPath('data.modules.0.name', 'Module A')
            ->assertJsonPath('data.modules.0.contents.0.title', 'PDF Content')
            ->assertJsonPath('data.modules.0.contents.0.pdf.file_path', 'course-pdfs/doc.pdf')
            ->assertJsonPath('data.modules.0.quiz', null);
    }

    public function test_admin_can_update_course_full_tree(): void
    {
        $token = $this->adminToken();
        $admin = $this->admin();

        $course  = CourseOnline::query()->create(['name' => 'Sync Course', 'created_by' => $admin->id]);
        $module1 = CourseModule::query()->create(['course_online_id' => $course->id, 'name' => 'Module 1', 'order_number' => 1]);
        $module2 = CourseModule::query()->create(['course_online_id' => $course->id, 'name' => 'Module 2', 'order_number' => 2]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->putJson("/api/admin/online-courses/update/{$course->id}", [
                'modules' => [
                    [
                        'id'           => $module1->id,
                        'name'         => 'Module 1 Renamed',
                        'order_number' => 1,
                    ],
                    [
                        'name'         => 'New Module',
                        'order_number' => 2,
                    ],
                ],
            ]);

        $response->assertOk();

        // Module 2 absent from payload → deleted
        $this->assertDatabaseMissing('course_modules', ['id' => $module2->id]);
        // Module 1 present with id → updated
        $this->assertDatabaseHas('course_modules', ['id' => $module1->id, 'name' => 'Module 1 Renamed']);
        // New module → created
        $this->assertDatabaseHas('course_modules', ['name' => 'New Module']);
        $this->assertDatabaseCount('course_modules', 2);
    }

    public function test_admin_can_update_course_replaces_pdf_file(): void
    {
        Storage::fake('local');
        $token = $this->adminToken();
        $admin = $this->admin();

        $oldPath = 'course-pdfs/old-file.pdf';
        Storage::disk('local')->put($oldPath, 'old content');

        $course  = CourseOnline::query()->create(['name' => 'PDF Test', 'created_by' => $admin->id]);
        $module  = CourseModule::query()->create(['course_online_id' => $course->id, 'name' => 'Mod', 'order_number' => 1]);
        $content = ModuleContent::query()->create([
            'module_id'    => $module->id,
            'title'        => 'PDF Item',
            'content_type' => 'pdf',
            'order_number' => 1,
        ]);
        ModuleContentPdf::query()->create([
            'module_content_id' => $content->id,
            'file_path'         => $oldPath,
        ]);

        $newPath  = 'course-pdfs/new-file.pdf';
        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->putJson("/api/admin/online-courses/update/{$course->id}", [
                'modules' => [
                    [
                        'id'           => $module->id,
                        'name'         => 'Mod',
                        'order_number' => 1,
                        'contents'     => [
                            [
                                'id'           => $content->id,
                                'title'        => 'PDF Item',
                                'order_number' => 1,
                                'pdf'          => ['file_path' => $newPath],
                            ],
                        ],
                    ],
                ],
            ]);

        $response->assertOk();
        Storage::disk('local')->assertMissing($oldPath);
        $this->assertDatabaseHas('module_content_pdfs', ['file_path' => $newPath]);
    }

    public function test_admin_can_delete_course_and_all_content(): void
    {
        Storage::fake('local');
        $token = $this->adminToken();
        $admin = $this->admin();

        $pdfPath = 'course-pdfs/to-delete.pdf';
        Storage::disk('local')->put($pdfPath, 'content');

        $course  = CourseOnline::query()->create(['name' => 'Delete Me', 'created_by' => $admin->id]);
        $module  = CourseModule::query()->create(['course_online_id' => $course->id, 'name' => 'M', 'order_number' => 1]);
        $content = ModuleContent::query()->create([
            'module_id'    => $module->id,
            'title'        => 'PDF',
            'content_type' => 'pdf',
            'order_number' => 1,
        ]);
        ModuleContentPdf::query()->create([
            'module_content_id' => $content->id,
            'file_path'         => $pdfPath,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->deleteJson("/api/admin/online-courses/delete/{$course->id}");

        $response->assertNoContent();
        $this->assertSoftDeleted('course_onlines', ['id' => $course->id]);
        $this->assertDatabaseMissing('course_modules', ['id' => $module->id]);
        $this->assertDatabaseMissing('module_contents', ['id' => $content->id]);
        Storage::disk('local')->assertMissing($pdfPath);
    }

    public function test_admin_cannot_delete_course_with_active_assignments(): void
    {
        $token = $this->adminToken();
        $admin = $this->admin();

        $course = CourseOnline::query()->create(['name' => 'Assigned', 'created_by' => $admin->id]);
        $user   = User::factory()->create(['role' => 'user']);

        CourseOnlineAssignment::query()->create([
            'course_online_id' => $course->id,
            'user_id'          => $user->id,
            'assigned_by'      => $admin->id,
            'assigned_at'      => now(),
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->deleteJson("/api/admin/online-courses/delete/{$course->id}");

        $response->assertStatus(422);
    }

    // -------------------------------------------------------------------------
    // getAll
    // -------------------------------------------------------------------------

    public function test_get_all_courses_returns_paginated_list_and_cards(): void
    {
        $token = $this->adminToken();
        $admin = $this->admin();

        CourseOnline::query()->create(['name' => 'Draft Course',     'status' => 'draft',     'created_by' => $admin->id]);
        CourseOnline::query()->create(['name' => 'Published Course', 'status' => 'published', 'created_by' => $admin->id]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/admin/online-courses/getAll');

        $response->assertOk()
            ->assertJsonStructure(['data', 'meta', 'cards']);

        $cards = collect($response->json('cards'));
        $this->assertNotNull($cards->firstWhere('key', 'total_courses'));
        $this->assertNotNull($cards->firstWhere('key', 'published_courses'));
        $this->assertNotNull($cards->firstWhere('key', 'draft_courses'));
        $this->assertSame(2, $response->json('meta.total'));
    }

    // -------------------------------------------------------------------------
    // upload-pdf
    // -------------------------------------------------------------------------

    public function test_admin_can_upload_pdf(): void
    {
        Storage::fake('local');
        $token = $this->adminToken();

        $file = UploadedFile::fake()->create('sample.pdf', 100, 'application/pdf');

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/admin/online-courses/upload-pdf', [
                'pdf_file' => $file,
            ]);

        $response->assertOk()
            ->assertJsonStructure(['file_path', 'file_size']);

        $filePath = $response->json('file_path');
        $this->assertStringStartsWith('course-pdfs/', $filePath);
        Storage::disk('local')->assertExists($filePath);
    }

    // -------------------------------------------------------------------------
    // modules/reorder
    // -------------------------------------------------------------------------

    public function test_admin_can_reorder_modules(): void
    {
        $token = $this->adminToken();
        $admin = $this->admin();

        $course = CourseOnline::query()->create(['name' => 'Reorder', 'created_by' => $admin->id]);
        $m1     = CourseModule::query()->create(['course_online_id' => $course->id, 'name' => 'M1', 'order_number' => 1]);
        $m2     = CourseModule::query()->create(['course_online_id' => $course->id, 'name' => 'M2', 'order_number' => 2]);
        $m3     = CourseModule::query()->create(['course_online_id' => $course->id, 'name' => 'M3', 'order_number' => 3]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->putJson('/api/admin/online-courses/modules/reorder', [
                'order' => [
                    ['module_id' => $m1->id, 'order_number' => 3],
                    ['module_id' => $m2->id, 'order_number' => 1],
                    ['module_id' => $m3->id, 'order_number' => 2],
                ],
            ]);

        $response->assertOk()->assertJsonPath('message', 'Modules reordered successfully.');

        $this->assertDatabaseHas('course_modules', ['id' => $m1->id, 'order_number' => 3]);
        $this->assertDatabaseHas('course_modules', ['id' => $m2->id, 'order_number' => 1]);
        $this->assertDatabaseHas('course_modules', ['id' => $m3->id, 'order_number' => 2]);
    }

    public function test_reorder_rejects_modules_from_different_courses(): void
    {
        $token = $this->adminToken();
        $admin = $this->admin();

        $course1 = CourseOnline::query()->create(['name' => 'C1', 'created_by' => $admin->id]);
        $course2 = CourseOnline::query()->create(['name' => 'C2', 'created_by' => $admin->id]);
        $m1      = CourseModule::query()->create(['course_online_id' => $course1->id, 'name' => 'M1', 'order_number' => 1]);
        $m2      = CourseModule::query()->create(['course_online_id' => $course2->id, 'name' => 'M2', 'order_number' => 1]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->putJson('/api/admin/online-courses/modules/reorder', [
                'order' => [
                    ['module_id' => $m1->id, 'order_number' => 1],
                    ['module_id' => $m2->id, 'order_number' => 2],
                ],
            ]);

        $response->assertStatus(422);
    }

    // -------------------------------------------------------------------------
    // Auth guard
    // -------------------------------------------------------------------------

    public function test_non_admin_cannot_access_course_endpoints(): void
    {
        $this->seed(DatabaseSeeder::class);

        $user  = User::factory()->create(['role' => 'user']);
        $token = (string) $this->postJson('/api/login', [
            'email'    => $user->email,
            'password' => 'password',
        ])->json('data.token');

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/admin/online-courses/getAll')
            ->assertForbidden();
    }
}