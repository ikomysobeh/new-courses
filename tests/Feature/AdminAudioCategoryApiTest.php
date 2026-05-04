<?php

namespace Tests\Feature;

use App\Models\Audio;
use App\Models\AudioCategory;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAudioCategoryApiTest extends TestCase
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

    public function test_admin_can_create_update_delete_category(): void
    {
        $token = $this->adminToken();

        $create = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/admin/audio-categories/create', [
                'name' => 'Leadership Audio',
                'sort_order' => 10,
            ]);

        $create->assertCreated()->assertJsonPath('data.name', 'Leadership Audio');
        $id = (int) $create->json('data.id');

        $update = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->putJson('/api/admin/audio-categories/update/' . $id, [
                'name' => 'Leadership Audio Advanced',
            ]);

        $update->assertOk()->assertJsonPath('data.slug', 'leadership-audio-advanced');

        $delete = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->deleteJson('/api/admin/audio-categories/delete/' . $id);

        $delete->assertOk();
        $this->assertDatabaseMissing('audio_categories', ['id' => $id]);
    }

    public function test_category_index_returns_cards(): void
    {
        $token = $this->adminToken();

        AudioCategory::query()->create([
            'name' => 'Card Category',
            'slug' => 'card-category',
            'sort_order' => 0,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/admin/audio-categories/getAll');

        $response->assertOk()
            ->assertJsonStructure([
                'data',
                'cards' => [
                    '*' => ['key', 'title', 'value'],
                ],
            ]);
    }

    public function test_cannot_delete_category_with_linked_audio(): void
    {
        $token = $this->adminToken();

        $category = AudioCategory::query()->create([
            'name' => 'Compliance',
            'slug' => 'compliance',
            'sort_order' => 0,
        ]);

        Audio::query()->create([
            'name' => 'Compliance Intro',
            'audio_category_id' => $category->id,
            'duration' => 60,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->deleteJson('/api/admin/audio-categories/delete/' . $category->id);

        $response->assertStatus(422);
    }
}
