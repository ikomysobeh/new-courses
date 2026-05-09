<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Video;
use App\Models\VideoCategory;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminVideoSubtitleApiTest extends TestCase
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

    private function makeVideo(): Video
    {
        $category = VideoCategory::query()->create(['name' => 'Sub Cat', 'slug' => 'sub-cat', 'sort_order' => 0]);
        $admin    = User::where('email', 'admin@newproject.test')->first();

        return Video::query()->create([
            'name'              => 'Subtitle Video',
            'file_path'         => 'videos/sub_video.mp4',
            'video_category_id' => $category->id,
            'transcode_status'  => 'completed',
            'created_by'        => $admin->id,
        ]);
    }

    public function test_admin_can_upload_subtitle(): void
    {
        Storage::fake('local');

        $token = $this->adminToken();
        $video = $this->makeVideo();

        $file = UploadedFile::fake()->createWithContent('subtitle.vtt', "WEBVTT\n\n00:00:01.000 --> 00:00:04.000\nHello world\n");

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/admin/videos/' . $video->id . '/subtitle', [
                'subtitle_file' => $file,
            ]);

        $response->assertOk()
            ->assertJsonPath('data.id', $video->id);

        $subtitlePath = $response->json('data.subtitle_vtt_path');
        $this->assertNotNull($subtitlePath);
        Storage::disk('local')->assertExists($subtitlePath);
    }

    public function test_admin_can_delete_subtitle(): void
    {
        Storage::fake('local');

        $token = $this->adminToken();
        $video = $this->makeVideo();

        $path = 'subtitles/' . $video->id . '_subtitle.vtt';
        Storage::disk('local')->put($path, "WEBVTT\n");

        $video->update(['subtitle_vtt_path' => $path]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->deleteJson('/api/admin/videos/' . $video->id . '/subtitle');

        $response->assertOk();
        Storage::disk('local')->assertMissing($path);
        $this->assertDatabaseHas('videos', ['id' => $video->id, 'subtitle_vtt_path' => null]);
    }

    public function test_delete_subtitle_fails_if_no_subtitle_set(): void
    {
        $token = $this->adminToken();
        $video = $this->makeVideo();

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->deleteJson('/api/admin/videos/' . $video->id . '/subtitle')
            ->assertStatus(422);
    }

    public function test_upload_subtitle_rejects_non_vtt_file(): void
    {
        Storage::fake('local');

        $token = $this->adminToken();
        $video = $this->makeVideo();

        $file = UploadedFile::fake()->create('subtitle.srt', 10);

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/admin/videos/' . $video->id . '/subtitle', [
                'subtitle_file' => $file,
            ])
            ->assertUnprocessable();
    }

    public function test_admin_can_get_subtitle_path(): void
    {
        Storage::fake('local');

        $token = $this->adminToken();
        $video = $this->makeVideo();

        $path = 'subtitles/' . $video->id . '_sub.vtt';
        Storage::disk('local')->put($path, "WEBVTT\n");
        $video->update(['subtitle_vtt_path' => $path]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/admin/videos/' . $video->id . '/subtitle');

        $response->assertOk()
            ->assertJsonPath('data.subtitle_vtt_path', $path);
    }
}
