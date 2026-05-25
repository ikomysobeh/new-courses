<?php

namespace Tests\Feature;

use App\Models\Audio;
use App\Models\Video;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class BlogMediaStreamApiTest extends TestCase
{
    use RefreshDatabase;

    private function adminId(): int
    {
        $this->seed(DatabaseSeeder::class);

        return User::where('role', 'admin')->first()->id;
    }

    // ---- blog video stream ----

    public function test_signed_url_streams_blog_video(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('videos/test.mp4', 'fake-video-content');

        $adminId = $this->adminId();
        $video   = Video::create([
            'name'             => 'Blog Video',
            'transcode_status' => 'completed',
            'hls_path'         => 'videos/test.mp4',
            'created_by'       => $adminId,
        ]);

        $url = URL::temporarySignedRoute('media.blog-video', now()->addHours(4), [
            'video_id' => $video->id,
        ]);

        $path = parse_url($url, PHP_URL_PATH);
        $query = parse_url($url, PHP_URL_QUERY);

        $this->get($path . '?' . $query)->assertOk();
    }

    public function test_unsigned_blog_video_url_is_rejected(): void
    {
        $this->seed(DatabaseSeeder::class);

        $video = Video::create([
            'name'             => 'Video',
            'transcode_status' => 'completed',
            'created_by'       => User::where('role', 'admin')->first()->id,
        ]);

        $this->get('/api/media/blog-video/' . $video->id)->assertForbidden();
    }

    public function test_blog_video_stream_returns_404_for_missing_video(): void
    {
        $this->seed(DatabaseSeeder::class);

        $url = URL::temporarySignedRoute('media.blog-video', now()->addHours(4), [
            'video_id' => 99999,
        ]);

        $path  = parse_url($url, PHP_URL_PATH);
        $query = parse_url($url, PHP_URL_QUERY);

        $this->get($path . '?' . $query)->assertNotFound();
    }

    // ---- blog audio stream ----

    public function test_signed_url_streams_blog_audio(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('audio/ep1.mp3', 'fake-audio-content');

        $adminId = $this->adminId();
        $audio   = Audio::create([
            'name'       => 'Episode 1',
            'local_path' => 'audio/ep1.mp3',
            'created_by' => $adminId,
        ]);

        $url = URL::temporarySignedRoute('media.blog-audio', now()->addHours(4), [
            'audio_id' => $audio->id,
        ]);

        $path  = parse_url($url, PHP_URL_PATH);
        $query = parse_url($url, PHP_URL_QUERY);

        $this->get($path . '?' . $query)->assertOk();
    }

    public function test_unsigned_blog_audio_url_is_rejected(): void
    {
        $this->seed(DatabaseSeeder::class);

        $audio = Audio::create([
            'name'       => 'Audio',
            'local_path' => 'audio/test.mp3',
            'created_by' => User::where('role', 'admin')->first()->id,
        ]);

        $this->get('/api/media/blog-audio/' . $audio->id)->assertForbidden();
    }

    public function test_blog_audio_stream_returns_404_for_missing_audio(): void
    {
        $this->seed(DatabaseSeeder::class);

        $url = URL::temporarySignedRoute('media.blog-audio', now()->addHours(4), [
            'audio_id' => 99999,
        ]);

        $path  = parse_url($url, PHP_URL_PATH);
        $query = parse_url($url, PHP_URL_QUERY);

        $this->get($path . '?' . $query)->assertNotFound();
    }

    // ---- expired signed URL ----

    public function test_expired_signed_url_is_rejected(): void
    {
        $this->seed(DatabaseSeeder::class);

        $video = Video::create([
            'name'             => 'Video',
            'transcode_status' => 'completed',
            'created_by'       => User::where('role', 'admin')->first()->id,
        ]);

        $url = URL::temporarySignedRoute('media.blog-video', now()->subMinute(), [
            'video_id' => $video->id,
        ]);

        $path  = parse_url($url, PHP_URL_PATH);
        $query = parse_url($url, PHP_URL_QUERY);

        $this->get($path . '?' . $query)->assertForbidden();
    }
}
