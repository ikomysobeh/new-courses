<?php

namespace Tests\Feature;

use App\Models\Audio;
use App\Models\AudioCategory;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminAudioApiTest extends TestCase
{
    use RefreshDatabase;

    private function adminToken(): string
    {
        $this->seed(DatabaseSeeder::class);

        return (string) $this->postJson('/api/login', [
            'email' => 'admin@newproject.test',
            'password' => env('ADMIN_INITIAL_PASSWORD', 'Admin@12345'),
        ])->json('data.token');
    }

    private function categoryId(): int
    {
        return (int) AudioCategory::query()->create([
            'name' => 'Operations',
            'slug' => 'operations',
            'sort_order' => 1,
        ])->id;
    }

    public function test_admin_can_create_audio_with_local_file(): void
    {
        Storage::fake('local');
        $token = $this->adminToken();

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/admin/audio/create', [
                'name' => 'Ops Basics',
                'audio_category_id' => $this->categoryId(),
                'duration' => 120,
                'audio_file' => UploadedFile::fake()->create('ops.mp3', 100, 'audio/mpeg'),
            ]);

        $response->assertCreated()->assertJsonPath('data.name', 'Ops Basics');
        $this->assertDatabaseHas('audios', ['name' => 'Ops Basics']);
    }

    public function test_admin_can_update_audio(): void
    {
        $token = $this->adminToken();
        $categoryId = $this->categoryId();

        $audio = Audio::query()->create([
            'name' => 'Old Audio',
            'audio_category_id' => $categoryId,
            'duration' => 100,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->putJson('/api/admin/audio/update/' . $audio->id, [
                'name' => 'New Audio',
                'duration' => 150,
            ]);

        $response->assertOk()->assertJsonPath('data.name', 'New Audio');
        $this->assertDatabaseHas('audios', ['id' => $audio->id, 'name' => 'New Audio', 'duration' => 150]);
    }

    public function test_admin_can_delete_audio_soft_delete(): void
    {
        $token = $this->adminToken();
        $audio = Audio::query()->create([
            'name' => 'Delete Me',
            'audio_category_id' => $this->categoryId(),
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->deleteJson('/api/admin/audio/delete/' . $audio->id);

        $response->assertOk();
        $this->assertSoftDeleted('audios', ['id' => $audio->id]);
    }

    public function test_admin_audio_index_returns_cards(): void
    {
        $token = $this->adminToken();

        Audio::query()->create([
            'name' => 'Cards Audio',
            'audio_category_id' => $this->categoryId(),
            'duration' => 90,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/admin/audio/getAll');

        $response->assertOk()
            ->assertJsonStructure([
                'data',
                'cards' => [
                    '*' => ['key', 'title', 'value'],
                ],
                'meta',
            ]);
    }
}
