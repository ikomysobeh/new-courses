<?php

namespace Tests\Feature;

use App\Mail\AudioAssignedManagerMail;
use App\Mail\AudioAssignedUserMail;
use App\Models\Audio;
use App\Models\AudioAssignment;
use App\Models\AudioCategory;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class AdminAudioAssignmentApiTest extends TestCase
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

    private function createAudio(): Audio
    {
        $category = AudioCategory::query()->create([
            'name' => 'Safety',
            'slug' => 'safety',
            'sort_order' => 1,
        ]);

        return Audio::query()->create([
            'name' => 'Safety Intro',
            'audio_category_id' => $category->id,
            'duration' => 100,
        ]);
    }

    public function test_assign_audio_to_multiple_users_and_prevent_duplicates(): void
    {
        Mail::fake();
        $token = $this->adminToken();
        $audio = $this->createAudio();

        $manager = User::factory()->create(['role' => 'user']);
        $userA = User::factory()->create(['report_to' => $manager->id]);
        $userB = User::factory()->create(['report_to' => $manager->id]);

        $first = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/admin/audio-assignments/create', [
                'audio_id' => $audio->id,
                'user_ids' => [$userA->id, $userB->id],
                'send_notification' => true,
            ]);

        $first->assertCreated()->assertJsonCount(2, 'data');
        $this->assertDatabaseCount('audio_assignments', 2);

        $second = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/admin/audio-assignments/create', [
                'audio_id' => $audio->id,
                'user_ids' => [$userA->id],
                'send_notification' => true,
            ]);

        $second->assertCreated();
        $this->assertSame([$userA->id], $second->json('skipped_user_ids'));
        $this->assertDatabaseCount('audio_assignments', 2);

        Mail::assertQueued(AudioAssignedUserMail::class, 2);
        Mail::assertQueued(AudioAssignedManagerMail::class, 1);
        Mail::assertQueued(AudioAssignedManagerMail::class, function (AudioAssignedManagerMail $mail) use ($manager) {
            return $mail->manager->id === $manager->id && $mail->teamMembers->count() === 2;
        });
    }

    public function test_get_all_audio_assignments_returns_cards(): void
    {
        $token = $this->adminToken();
        $audio = $this->createAudio();
        $admin = User::query()->where('email', 'admin@newproject.test')->firstOrFail();
        $user = User::factory()->create();

        AudioAssignment::query()->create([
            'audio_id' => $audio->id,
            'user_id' => $user->id,
            'assigned_by' => $admin->id,
            'assigned_at' => now(),
            'notification_sent' => false,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/admin/audio-assignments/getAll');

        $response->assertOk()->assertJsonStructure([
            'data',
            'meta',
            'cards' => [
                '*' => ['key', 'title', 'value'],
            ],
        ]);
        $response->assertJsonPath('cards.0.key', 'total_audio_assignments');
    }

    public function test_delete_assignment_hard_deletes_record(): void
    {
        $token = $this->adminToken();
        $audio = $this->createAudio();
        $admin = User::query()->where('email', 'admin@newproject.test')->firstOrFail();
        $user = User::factory()->create();

        $assignment = AudioAssignment::query()->create([
            'audio_id' => $audio->id,
            'user_id' => $user->id,
            'assigned_by' => $admin->id,
            'assigned_at' => now(),
            'notification_sent' => false,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->deleteJson('/api/admin/audio-assignments/delete/' . $assignment->id);

        $response->assertOk();
        $this->assertDatabaseMissing('audio_assignments', ['id' => $assignment->id]);
    }

    public function test_non_admin_cannot_call_admin_audio_assignment_endpoints(): void
    {
        $this->seed(DatabaseSeeder::class);

        $user = User::factory()->create(['role' => 'user']);
        $token = $user->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/admin/audio-assignments/getAll');

        $response->assertStatus(403);
    }

    public function test_manager_notification_is_not_sent_when_users_have_no_manager(): void
    {
        Mail::fake();
        $token = $this->adminToken();
        $audio = $this->createAudio();

        $userA = User::factory()->create(['report_to' => null]);
        $userB = User::factory()->create(['report_to' => null]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/admin/audio-assignments/create', [
                'audio_id' => $audio->id,
                'user_ids' => [$userA->id, $userB->id],
                'send_notification' => true,
            ]);

        $response->assertCreated();

        Mail::assertQueued(AudioAssignedUserMail::class, 2);
        Mail::assertNotQueued(AudioAssignedManagerMail::class);
    }
}
