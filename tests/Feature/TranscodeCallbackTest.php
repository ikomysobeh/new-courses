<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Video;
use App\Models\VideoCategory;
use App\Models\VideoQuality;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TranscodeCallbackTest extends TestCase
{
    use RefreshDatabase;

    private function makeVideo(string $status = 'processing'): Video
    {
        $this->seed(DatabaseSeeder::class);

        $category = VideoCategory::query()->create(['name' => 'Trans Cat', 'slug' => 'trans-cat', 'sort_order' => 0]);
        $admin    = User::where('email', 'admin@newproject.test')->first();

        return Video::query()->create([
            'name'              => 'Transcode Video',
            'file_path'         => 'videos/transcode.mp4',
            'video_category_id' => $category->id,
            'transcode_status'  => $status,
            'created_by'        => $admin->id,
        ]);
    }

    public function test_transcode_callback_marks_video_completed_and_saves_qualities(): void
    {
        $video = $this->makeVideo();

        $response = $this->postJson('/api/transcode/callback', [
            'video_id' => $video->id,
            'status'   => 'completed',
            'qualities' => [
                ['quality' => '360p', 'file_path' => 'videos/' . $video->id . '/360p.mp4', 'file_size' => 1000000],
                ['quality' => '720p', 'file_path' => 'videos/' . $video->id . '/720p.mp4', 'file_size' => 3000000],
            ],
        ]);

        $response->assertOk();

        $this->assertDatabaseHas('videos', ['id' => $video->id, 'transcode_status' => 'completed']);
        $this->assertDatabaseHas('video_qualities', ['video_id' => $video->id, 'quality' => '360p']);
        $this->assertDatabaseHas('video_qualities', ['video_id' => $video->id, 'quality' => '720p']);
    }

    public function test_transcode_callback_marks_video_failed(): void
    {
        $video = $this->makeVideo();

        $response = $this->postJson('/api/transcode/callback', [
            'video_id' => $video->id,
            'status'   => 'failed',
            'qualities' => [],
        ]);

        $response->assertOk();
        $this->assertDatabaseHas('videos', ['id' => $video->id, 'transcode_status' => 'failed']);
    }

    public function test_transcode_callback_returns_404_for_unknown_video(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->postJson('/api/transcode/callback', [
            'video_id' => 999999,
            'status'   => 'completed',
            'qualities' => [],
        ])->assertNotFound();
    }

    public function test_transcode_callback_upserts_existing_quality(): void
    {
        $video = $this->makeVideo();

        VideoQuality::query()->create([
            'video_id'  => $video->id,
            'quality'   => '720p',
            'file_path' => 'videos/old_720p.mp4',
            'file_size' => 1000,
        ]);

        $this->postJson('/api/transcode/callback', [
            'video_id' => $video->id,
            'status'   => 'completed',
            'qualities' => [
                ['quality' => '720p', 'file_path' => 'videos/new_720p.mp4', 'file_size' => 5000000],
            ],
        ])->assertOk();

        $this->assertDatabaseHas('video_qualities', [
            'video_id'  => $video->id,
            'quality'   => '720p',
            'file_path' => 'videos/new_720p.mp4',
        ]);
        $this->assertDatabaseCount('video_qualities', 1);
    }
}
