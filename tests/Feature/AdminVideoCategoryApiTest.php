<?php

namespace Tests\Feature;

use App\Models\Video;
use App\Models\VideoCategory;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminVideoCategoryApiTest extends TestCase
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

    public function test_admin_can_create_update_delete_category(): void
    {
        $token = $this->adminToken();

        $create = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/admin/video-categories/create', [
                'name'       => 'Leadership Videos',
                'sort_order' => 5,
            ]);

        $create->assertCreated()
            ->assertJsonPath('data.name', 'Leadership Videos')
            ->assertJsonPath('data.slug', 'leadership-videos');

        $id = (int) $create->json('data.id');

        $update = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->putJson('/api/admin/video-categories/update/' . $id, [
                'name' => 'Leadership Videos Advanced',
            ]);

        $update->assertOk()
            ->assertJsonPath('data.slug', 'leadership-videos-advanced');

        $delete = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->deleteJson('/api/admin/video-categories/delete/' . $id);

        $delete->assertOk();
        $this->assertDatabaseMissing('video_categories', ['id' => $id]);
    }

    public function test_admin_can_get_all_categories_with_cards(): void
    {
        $token = $this->adminToken();

        VideoCategory::query()->create(['name' => 'Cat A', 'slug' => 'cat-a', 'sort_order' => 0]);
        VideoCategory::query()->create(['name' => 'Cat B', 'slug' => 'cat-b', 'sort_order' => 1]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/admin/video-categories/getAll');

        $response->assertOk()
            ->assertJsonStructure([
                'data',
                'cards' => [['key', 'title', 'value']],
            ]);

        $keys = array_column($response->json('cards'), 'key');
        $this->assertContains('total_categories', $keys);
        $this->assertContains('total_videos', $keys);
    }

    public function test_cannot_delete_category_with_videos(): void
    {
        $token = $this->adminToken();

        $category = VideoCategory::query()->create(['name' => 'Training', 'slug' => 'training', 'sort_order' => 0]);

        // Need a user for created_by
        $adminUser = \App\Models\User::where('email', 'admin@newproject.test')->first();

        Video::query()->create([
            'name'              => 'Intro Video',
            'file_path'         => 'videos/intro.mp4',
            'video_category_id' => $category->id,
            'transcode_status'  => 'pending',
            'created_by'        => $adminUser->id,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->deleteJson('/api/admin/video-categories/delete/' . $category->id);

        $response->assertStatus(422);
        $this->assertDatabaseHas('video_categories', ['id' => $category->id]);
    }

    public function test_non_admin_cannot_access_category_endpoints(): void
    {
        $this->seed(DatabaseSeeder::class);

        $user = \App\Models\User::factory()->create(['role' => 'user']);

        $token = (string) $this->postJson('/api/login', [
            'email'    => $user->email,
            'password' => 'password',
        ])->json('data.token');

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/admin/video-categories/getAll')
            ->assertForbidden();
    }
}
