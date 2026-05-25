<?php

namespace Tests\Feature;

use App\Models\Audio;
use App\Models\Video;
use App\Models\PodcastPost;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BlogMediaSelectionApiTest extends TestCase
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

    private function adminId(): int
    {
        return User::where('role', 'admin')->first()->id;
    }

    // ---- available videos ----

    public function test_available_videos_only_shows_completed_transcodes(): void
    {
        $token = $this->adminToken();

        Video::create([
            'name'             => 'Completed Video',
            'transcode_status' => 'completed',
            'created_by'       => $this->adminId(),
        ]);

        Video::create([
            'name'             => 'Pending Video',
            'transcode_status' => 'pending',
            'created_by'       => $this->adminId(),
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/admin/blog-posts/available-videos')
            ->assertOk();

        $names = collect($response->json('data'))->pluck('name')->toArray();
        $this->assertContains('Completed Video', $names);
        $this->assertNotContains('Pending Video', $names);
    }

    // ---- available audios ----

    public function test_available_audios_only_shows_audios_with_local_path(): void
    {
        $token = $this->adminToken();

        Audio::create([
            'name'       => 'Uploaded Audio',
            'local_path' => 'audio/test.mp3',
            'created_by' => $this->adminId(),
        ]);

        Audio::create([
            'name'       => 'Pending Audio',
            'local_path' => null,
            'created_by' => $this->adminId(),
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/admin/blog-posts/available-audios')
            ->assertOk();

        $names = collect($response->json('data'))->pluck('name')->toArray();
        $this->assertContains('Uploaded Audio', $names);
        $this->assertNotContains('Pending Audio', $names);
    }

    // ---- media attachment on create ----

    public function test_create_post_with_audio_media(): void
    {
        \Illuminate\Support\Facades\Storage::fake('public');
        $token = $this->adminToken();

        $audio = Audio::create([
            'name'       => 'Episode 1',
            'local_path' => 'audio/ep1.mp3',
            'created_by' => $this->adminId(),
        ]);

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/admin/blog-posts', [
                'title'         => 'Audio Post',
                'status'        => 'draft',
                'mediable_type' => 'App\\Models\\Audio',
                'mediable_id'   => $audio->id,
            ])
            ->assertCreated()
            ->assertJsonPath('data.mediable_type', 'App\\Models\\Audio')
            ->assertJsonPath('data.mediable_id', $audio->id);
    }

    public function test_create_rejects_mediable_id_without_mediable_type(): void
    {
        $token = $this->adminToken();

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/admin/blog-posts', [
                'title'       => 'Bad Post',
                'status'      => 'draft',
                'mediable_id' => 1,
            ])
            ->assertUnprocessable();
    }

    public function test_create_rejects_invalid_mediable_type(): void
    {
        $token = $this->adminToken();

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/admin/blog-posts', [
                'title'         => 'Bad Post',
                'status'        => 'draft',
                'mediable_type' => 'App\\Models\\User',
                'mediable_id'   => 1,
            ])
            ->assertUnprocessable();
    }
}
