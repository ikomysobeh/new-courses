<?php

namespace Tests\Unit;

use App\Models\Audio;
use App\Models\AudioAssignment;
use App\Models\AudioCategory;
use App\Models\AudioProgress;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AudioModelRelationshipTest extends TestCase
{
    use RefreshDatabase;

    public function test_audio_model_relationships_can_be_eager_loaded(): void
    {
        $this->seed(DatabaseSeeder::class);

        $admin = User::query()->where('email', 'admin@newproject.test')->firstOrFail();
        $user = User::factory()->create(['role' => 'user']);

        $category = AudioCategory::query()->create([
            'name' => 'Relations',
            'slug' => 'relations',
            'sort_order' => 0,
        ]);

        $audio = Audio::query()->create([
            'name' => 'Relations Audio',
            'audio_category_id' => $category->id,
            'duration' => 120,
        ]);

        AudioAssignment::query()->create([
            'audio_id' => $audio->id,
            'user_id' => $user->id,
            'assigned_by' => $admin->id,
            'assigned_at' => now(),
            'notification_sent' => false,
        ]);

        AudioProgress::query()->create([
            'user_id' => $user->id,
            'audio_id' => $audio->id,
            'current_time' => 12.5,
            'total_listened_time' => 30,
            'completion_percentage' => 10,
            'is_completed' => false,
        ]);

        $loadedAudio = Audio::query()->with(['audioCategory', 'assignments.user', 'progress.user'])->findOrFail($audio->id);

        $this->assertTrue($loadedAudio->relationLoaded('audioCategory'));
        $this->assertTrue($loadedAudio->relationLoaded('assignments'));
        $this->assertTrue($loadedAudio->relationLoaded('progress'));
        $this->assertSame('Relations', $loadedAudio->audioCategory->name);
        $this->assertCount(1, $loadedAudio->assignments);
        $this->assertCount(1, $loadedAudio->progress);
        $this->assertSame($user->id, $loadedAudio->assignments->first()->user->id);
        $this->assertSame($user->id, $loadedAudio->progress->first()->user->id);
    }
}
