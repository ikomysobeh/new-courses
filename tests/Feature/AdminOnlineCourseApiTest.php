<?php

namespace Tests\Feature;

use App\Models\CourseModule;
use App\Models\CourseOnline;
use App\Models\ModuleContent;
use App\Models\User;
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

    // -------------------------------------------------------------------------
    // getAll
    // -------------------------------------------------------------------------

    public function test_get_all_online_courses_returns_paginated_list_with_cards(): void
    {
        $token = $this->adminToken();

        CourseOnline::query()->create([
            'name'       => 'Course A',
            'status'     => 'published',
            'created_by' => $this->admin()->id,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/admin/online-courses/getAll');

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

    public function test_create_online_course_without_modules(): void
    {
        $token = $this->adminToken();

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/admin/online-courses/create', [
                'name'   => 'Laravel Basics',
                'status' => 'draft',
            ]);

        $response->assertCreated()
            ->assertJsonPath('data.name', 'Laravel Basics');

        $this->assertDatabaseHas('course_onlines', ['name' => 'Laravel Basics']);
        $this->assertDatabaseHas('course_analytics', ['total_modules' => 0]);
    }

    public function test_create_online_course_with_full_module_and_text_content(): void
    {
        $token = $this->adminToken();

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/admin/online-courses/create', [
                'name'    => 'Advanced PHP',
                'status'  => 'published',
                'modules' => [
                    [
                        'name'         => 'Module 1',
                        'order_number' => 1,
                        'contents'     => [
                            [
                                'name'         => 'Intro Text',
                                'content_type' => 'text',
                                'order_number' => 1,
                                'text_body'    => 'Hello world',
                            ],
                        ],
                    ],
                ],
            ]);

        $response->assertCreated()
            ->assertJsonPath('data.name', 'Advanced PHP');

        $this->assertDatabaseHas('course_modules', ['name' => 'Module 1']);
        $this->assertDatabaseHas('module_contents', ['name' => 'Intro Text', 'content_type' => 'text']);
        $this->assertDatabaseHas('course_analytics', ['total_modules' => 1, 'total_contents' => 1]);
    }

    public function test_create_requires_name(): void
    {
        $token = $this->adminToken();

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/admin/online-courses/create', []);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['name']);
    }

    // -------------------------------------------------------------------------
    // getById
    // -------------------------------------------------------------------------

    public function test_get_by_id_returns_course_with_modules_and_contents(): void
    {
        $token   = $this->adminToken();
        $admin   = $this->admin();

        $course = CourseOnline::query()->create([
            'name'       => 'Deep Dive',
            'created_by' => $admin->id,
        ]);
        $module = CourseModule::query()->create([
            'course_online_id' => $course->id,
            'name'             => 'Module A',
            'order_number'     => 1,
        ]);
        ModuleContent::query()->create([
            'module_id'    => $module->id,
            'name'         => 'Content 1',
            'content_type' => 'text',
            'order_number' => 1,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson("/api/admin/online-courses/getById/{$course->id}");

        $response->assertOk()
            ->assertJsonPath('data.id', $course->id)
            ->assertJsonPath('data.modules.0.name', 'Module A')
            ->assertJsonPath('data.modules.0.contents.0.name', 'Content 1');
    }

    // -------------------------------------------------------------------------
    // update
    // -------------------------------------------------------------------------

    public function test_update_course_name_only(): void
    {
        $token = $this->adminToken();
        $admin = $this->admin();

        $course = CourseOnline::query()->create([
            'name'       => 'Old Name',
            'created_by' => $admin->id,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->putJson("/api/admin/online-courses/update/{$course->id}", [
                'name' => 'New Name',
            ]);

        $response->assertOk()
            ->assertJsonPath('data.name', 'New Name');

        $this->assertDatabaseHas('course_onlines', ['id' => $course->id, 'name' => 'New Name']);
    }

    public function test_update_syncs_modules_adds_and_removes(): void
    {
        $token = $this->adminToken();
        $admin = $this->admin();

        $course = CourseOnline::query()->create([
            'name'       => 'Sync Course',
            'created_by' => $admin->id,
        ]);
        $existingModule = CourseModule::query()->create([
            'course_online_id' => $course->id,
            'name'             => 'Old Module',
            'order_number'     => 1,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->putJson("/api/admin/online-courses/update/{$course->id}", [
                'modules' => [
                    [
                        'name'         => 'New Module',
                        'order_number' => 1,
                    ],
                ],
            ]);

        $response->assertOk();

        $this->assertDatabaseMissing('course_modules', ['id' => $existingModule->id]);
        $this->assertDatabaseHas('course_modules', ['name' => 'New Module']);
    }

    // -------------------------------------------------------------------------
    // delete
    // -------------------------------------------------------------------------

    public function test_delete_soft_deletes_course(): void
    {
        $token = $this->adminToken();
        $admin = $this->admin();

        $course = CourseOnline::query()->create([
            'name'       => 'To Delete',
            'created_by' => $admin->id,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->deleteJson("/api/admin/online-courses/delete/{$course->id}");

        $response->assertOk()
            ->assertJsonPath('message', 'Online course deleted successfully.');

        $this->assertSoftDeleted('course_onlines', ['id' => $course->id]);
    }

    // -------------------------------------------------------------------------
    // upload-pdf
    // -------------------------------------------------------------------------

    public function test_upload_pdf_returns_file_metadata(): void
    {
        Storage::fake('local');
        $token = $this->adminToken();

        $file = UploadedFile::fake()->create('sample.pdf', 100, 'application/pdf');

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/admin/online-courses/upload-pdf', [
                'pdf' => $file,
            ]);

        $response->assertOk()
            ->assertJsonStructure(['file_path', 'original_filename', 'file_size']);

        $this->assertSame('sample.pdf', $response->json('original_filename'));
    }

    // -------------------------------------------------------------------------
    // modules/reorder
    // -------------------------------------------------------------------------

    public function test_reorder_modules(): void
    {
        $token = $this->adminToken();
        $admin = $this->admin();

        $course = CourseOnline::query()->create([
            'name'       => 'Reorder Course',
            'created_by' => $admin->id,
        ]);
        $m1 = CourseModule::query()->create([
            'course_online_id' => $course->id, 'name' => 'M1', 'order_number' => 1,
        ]);
        $m2 = CourseModule::query()->create([
            'course_online_id' => $course->id, 'name' => 'M2', 'order_number' => 2,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->putJson('/api/admin/online-courses/modules/reorder', [
                'course_online_id' => $course->id,
                'modules'          => [
                    ['id' => $m1->id, 'order_number' => 2],
                    ['id' => $m2->id, 'order_number' => 1],
                ],
            ]);

        $response->assertOk()->assertJsonPath('message', 'Modules reordered successfully.');
        $this->assertDatabaseHas('course_modules', ['id' => $m1->id, 'order_number' => 2]);
        $this->assertDatabaseHas('course_modules', ['id' => $m2->id, 'order_number' => 1]);
    }

    // -------------------------------------------------------------------------
    // Auth guards
    // -------------------------------------------------------------------------

    public function test_unauthenticated_request_is_rejected(): void
    {
        $this->getJson('/api/admin/online-courses/getAll')->assertUnauthorized();
    }
}
