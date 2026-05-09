<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\VideoCategory;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class VideoChunkUploadTest extends TestCase
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

    public function test_single_chunk_upload_auto_finalizes(): void
    {
        Storage::fake('local');

        $token = $this->adminToken();
        $uuid  = (string) \Illuminate\Support\Str::uuid();

        $chunk = UploadedFile::fake()->create('video.mp4', 512);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/admin/videos/upload-chunk', [
                'chunk'             => $chunk,
                'upload_uuid'       => $uuid,
                'chunk_index'       => 0,
                'total_chunks'      => 1,
                'original_filename' => 'video.mp4',
            ]);

        $response->assertOk()
            ->assertJsonPath('status', 'complete');

        $filePath = $response->json('file_path');
        $this->assertNotNull($filePath);
        Storage::disk('local')->assertExists($filePath);
    }

    public function test_multi_chunk_upload_and_finalize(): void
    {
        Storage::fake('local');

        $token = $this->adminToken();
        $uuid  = (string) \Illuminate\Support\Str::uuid();

        // Upload chunk 0 — not yet complete
        $chunk0 = UploadedFile::fake()->create('part0.bin', 256);

        $resp0 = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/admin/videos/upload-chunk', [
                'chunk'             => $chunk0,
                'upload_uuid'       => $uuid,
                'chunk_index'       => 0,
                'total_chunks'      => 2,
                'original_filename' => 'bigvideo.mp4',
            ]);

        $resp0->assertOk()->assertJsonPath('status', 'pending');

        // Upload chunk 1 — triggers finalize
        $chunk1 = UploadedFile::fake()->create('part1.bin', 256);

        $resp1 = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/admin/videos/upload-chunk', [
                'chunk'        => $chunk1,
                'upload_uuid'  => $uuid,
                'chunk_index'  => 1,
                'total_chunks' => 2,
            ]);

        $resp1->assertOk()->assertJsonPath('status', 'complete');

        $filePath = $resp1->json('file_path');
        $this->assertNotNull($filePath);
        Storage::disk('local')->assertExists($filePath);

        // Temp directory should be cleaned up
        Storage::disk('local')->assertMissing("chunks/{$uuid}");
    }

    public function test_revert_chunk_deletes_temp_directory(): void
    {
        Storage::fake('local');

        $token = $this->adminToken();
        $uuid  = (string) \Illuminate\Support\Str::uuid();

        // Upload one chunk
        $chunk = UploadedFile::fake()->create('video.mp4', 256);

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/admin/videos/upload-chunk', [
                'chunk'             => $chunk,
                'upload_uuid'       => $uuid,
                'chunk_index'       => 0,
                'total_chunks'      => 2,
                'original_filename' => 'video.mp4',
            ])->assertOk();

        Storage::disk('local')->assertExists("chunks/{$uuid}/chunk_0");

        // Revert
        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->deleteJson('/api/admin/videos/upload-chunk/revert', [
                'upload_uuid' => $uuid,
            ])->assertOk();

        Storage::disk('local')->assertMissing("chunks/{$uuid}");
    }

    public function test_upload_chunk_rejects_invalid_uuid(): void
    {
        Storage::fake('local');

        $token = $this->adminToken();
        $chunk = UploadedFile::fake()->create('video.mp4', 128);

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/admin/videos/upload-chunk', [
                'chunk'             => $chunk,
                'upload_uuid'       => '../../../etc/passwd',
                'chunk_index'       => 0,
                'total_chunks'      => 1,
                'original_filename' => 'video.mp4',
            ])->assertStatus(422);
    }
}
