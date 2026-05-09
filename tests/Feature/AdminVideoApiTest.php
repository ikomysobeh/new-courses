<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Video;
use App\Models\VideoCategory;
use App\Models\VideoQuality;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminVideoApiTest extends TestCase
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

    private function makeCategory(): VideoCategory
    {
        return VideoCategory::query()->create(['name' => 'Test Cat', 'slug' => 'test-cat', 'sort_order' => 0]);
    }

    private function makeVideo(VideoCategory $category, array $overrides = []): Video
    {
        $admin = User::where('email', 'admin@newproject.test')->first();

        return Video::query()->create(array_merge([
            'name'              => 'Sample Video',
            'file_path'         => 'videos/sample.mp4',
            'video_category_id' => $category->id,
            'transcode_status'  => 'pending',
            'created_by'        => $admin->id,
        ], $overrides));
    }

    public function test_admin_can_create_video(): void
    {
        $token    = $this->adminToken();
        $category = $this->makeCategory();

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/admin/videos/create', [
                'name'              => 'Onboarding Video',
                'video_category_id' => $category->id,
                'file_path'         => 'videos/uuid_onboarding.mp4',
                'file_size'         => 5000000,
            ]);

        $response->assertCreated()
            ->assertJsonPath('data.name', 'Onboarding Video')
            ->assertJsonPath('data.transcode_status', 'pending');

        $this->assertDatabaseHas('videos', ['name' => 'Onboarding Video', 'transcode_status' => 'pending']);
    }

    public function test_admin_can_update_video(): void
    {
        $token    = $this->adminToken();
        $category = $this->makeCategory();
        $video    = $this->makeVideo($category);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->putJson('/api/admin/videos/update/' . $video->id, [
                'name' => 'Updated Video Name',
            ]);

        $response->assertOk()->assertJsonPath('data.name', 'Updated Video Name');
    }

    public function test_admin_can_soft_delete_video(): void
    {
        $token    = $this->adminToken();
        $category = $this->makeCategory();
        $video    = $this->makeVideo($category);

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->deleteJson('/api/admin/videos/delete/' . $video->id)
            ->assertOk();

        $this->assertSoftDeleted('videos', ['id' => $video->id]);
    }

    public function test_admin_can_get_video_by_id_with_qualities(): void
    {
        $token    = $this->adminToken();
        $category = $this->makeCategory();
        $video    = $this->makeVideo($category);

        VideoQuality::query()->create([
            'video_id'  => $video->id,
            'quality'   => '720p',
            'file_path' => 'videos/' . $video->id . '/720p.mp4',
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/admin/videos/getById/' . $video->id);

        $response->assertOk()
            ->assertJsonPath('data.id', $video->id)
            ->assertJsonStructure(['data' => ['qualities']]);

        $this->assertCount(1, $response->json('data.qualities'));
    }

    public function test_admin_can_get_all_videos_with_cards(): void
    {
        $token    = $this->adminToken();
        $category = $this->makeCategory();
        $this->makeVideo($category);
        $this->makeVideo($category, ['name' => 'Second Video']);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/admin/videos/getAll');

        $response->assertOk()
            ->assertJsonStructure(['data', 'cards' => [['key', 'title', 'value']]]);

        $keys = array_column($response->json('cards'), 'key');
        $this->assertContains('total_videos', $keys);
        $this->assertContains('pending_transcode', $keys);
    }

    public function test_admin_can_retry_transcode(): void
    {
        $token    = $this->adminToken();
        $category = $this->makeCategory();
        $video    = $this->makeVideo($category, ['transcode_status' => 'failed']);

        VideoQuality::query()->create(['video_id' => $video->id, 'quality' => '360p', 'file_path' => 'videos/360p.mp4']);
        VideoQuality::query()->create(['video_id' => $video->id, 'quality' => '720p', 'file_path' => 'videos/720p.mp4']);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/admin/videos/' . $video->id . '/retry-transcode');

        $response->assertOk()->assertJsonPath('data.transcode_status', 'pending');

        $this->assertDatabaseHas('videos', ['id' => $video->id, 'transcode_status' => 'pending']);
        $this->assertDatabaseMissing('video_qualities', ['video_id' => $video->id]);
    }

    public function test_non_admin_cannot_access_video_endpoints(): void
    {
        $this->seed(DatabaseSeeder::class);

        $user  = User::factory()->create(['role' => 'user']);
        $token = (string) $this->postJson('/api/login', [
            'email'    => $user->email,
            'password' => 'password',
        ])->json('data.token');

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/admin/videos/getAll')
            ->assertForbidden();
    }
}
