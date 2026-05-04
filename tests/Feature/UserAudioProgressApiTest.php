<?php

namespace Tests\Feature;

use App\Models\Audio;
use App\Models\AudioAssignment;
use App\Models\AudioCategory;
use App\Models\AudioProgress;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserAudioProgressApiTest extends TestCase
{
    use RefreshDatabase;

    private function seedBase(): void
    {
        $this->seed(DatabaseSeeder::class);
    }

    private function makeAudio(int $duration = 100): Audio
    {
        $category = AudioCategory::query()->create([
            'name' => 'Growth',
            'slug' => 'growth',
            'sort_order' => 1,
        ]);

        return Audio::query()->create([
            'name' => 'Growth Audio',
            'audio_category_id' => $category->id,
            'duration' => $duration,
        ]);
    }

    private function assignAudio(User $admin, User $user, Audio $audio): void
    {
        AudioAssignment::query()->create([
            'audio_id' => $audio->id,
            'user_id' => $user->id,
            'assigned_by' => $admin->id,
            'assigned_at' => now(),
            'notification_sent' => false,
        ]);
    }

    public function test_user_gets_assigned_audio_list(): void
    {
        $this->seedBase();

        $admin = User::query()->where('email', 'admin@newproject.test')->firstOrFail();
        $user = User::factory()->create(['role' => 'user']);
        $audio = $this->makeAudio();
        $this->assignAudio($admin, $user, $audio);

        $token = $user->createToken('user')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/user/audio/getAll');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonStructure([
                'cards' => [
                    '*' => ['key', 'title', 'value'],
                ],
            ]);
    }

    public function test_batched_progress_update_and_auto_complete(): void
    {
        $this->seedBase();

        $admin = User::query()->where('email', 'admin@newproject.test')->firstOrFail();
        $user = User::factory()->create(['role' => 'user']);
        $audio = $this->makeAudio(100);
        $this->assignAudio($admin, $user, $audio);

        $token = $user->createToken('user')->plainTextToken;

        $update = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/user/audio/progress/update/' . $audio->id, [
                'batch_key' => 'batch-001',
                'chunks' => [
                    ['current_time' => 60.5, 'listened_time' => 61],
                    ['current_time' => 96.0, 'listened_time' => 35],
                ],
            ]);

        $update->assertOk()
            ->assertJsonPath('data.is_completed', true)
            ->assertJsonPath('data.completion_percentage', 100);

        $duplicate = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/user/audio/progress/update/' . $audio->id, [
                'batch_key' => 'batch-001',
                'chunks' => [
                    ['current_time' => 96.0, 'listened_time' => 35],
                ],
            ]);

        $duplicate->assertOk();

        $progress = AudioProgress::query()->where('user_id', $user->id)->where('audio_id', $audio->id)->firstOrFail();
        $this->assertEquals(96.0, (float) $progress->current_time);
        $this->assertEquals(96, (int) $progress->total_listened_time);
        $this->assertTrue((bool) $progress->is_completed);
    }

    public function test_user_cannot_stream_audio_without_assignment(): void
    {
        $this->seedBase();

        $user = User::factory()->create(['role' => 'user']);
        $audio = $this->makeAudio();

        $token = $user->createToken('user')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->get('/api/user/audio/stream/' . $audio->id);

        $response->assertStatus(403);
    }

    public function test_no_manual_complete_endpoint_available(): void
    {
        $this->seedBase();
        $user = User::factory()->create(['role' => 'user']);
        $audio = $this->makeAudio();

        $token = $user->createToken('user')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/user/audio/progress/complete/' . $audio->id);

        $response->assertStatus(404);
    }
}
