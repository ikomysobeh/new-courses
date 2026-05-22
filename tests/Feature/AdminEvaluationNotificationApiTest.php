<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Evaluation;
use App\Models\EvaluationConfig;
use App\Models\EvaluationHistory;
use App\Models\EvaluationType;
use App\Models\NotificationSend;
use App\Models\User;
use App\Models\UserNotification;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class AdminEvaluationNotificationApiTest extends TestCase
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

    private function createManagerWithSubordinates(): array
    {
        $dept    = Department::create(['name' => 'Eng', 'slug' => 'eng']);
        $manager = User::factory()->create(['department_id' => $dept->id, 'role' => 'user']);
        $employee = User::factory()->create([
            'department_id' => $dept->id,
            'report_to'     => $manager->id,
        ]);

        return compact('dept', 'manager', 'employee');
    }

    public function test_preview_returns_correct_counts_without_dispatching_mail(): void
    {
        Mail::fake();
        $token   = $this->adminToken();
        $data    = $this->createManagerWithSubordinates();

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/admin/evaluation-notifications/preview', [
                'user_ids' => [$data['employee']->id],
                'subject'  => 'Evaluation Report Q1',
                'message'  => 'Please review the evaluations.',
            ]);

        $response->assertOk()
            ->assertJsonPath('data.employee_count', 1);

        Mail::assertNothingSent();
    }

    public function test_send_dispatches_queued_mail_and_writes_notification_records(): void
    {
        Mail::fake();
        $token    = $this->adminToken();
        $data     = $this->createManagerWithSubordinates();

        // Create an evaluation so the service finds records to send
        Evaluation::create([
            'user_id'           => $data['employee']->id,
            'department_id'     => $data['dept']->id,
            'course_type'       => 'regular',
            'total_score'       => 80,
            'performance_level' => 1,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/admin/evaluation-notifications/send', [
                'user_ids' => [$data['employee']->id],
                'subject'  => 'Evaluation Report Q1',
                'message'  => 'Please review the evaluations.',
            ]);

        $response->assertOk()
            ->assertJsonPath('success_count', 1)
            ->assertJsonPath('failed_count', 0);

        Mail::assertQueued(\App\Mail\EvaluationReportMail::class);
        $this->assertDatabaseCount('notification_sends', 1);
        $this->assertDatabaseCount('user_notifications', 1);
        $this->assertDatabaseHas('notification_sends', ['status' => 'sent', 'type' => 'evaluation_report']);
        $this->assertDatabaseHas('user_notifications', ['user_id' => $data['manager']->id]);
    }

    public function test_send_requires_user_ids(): void
    {
        $token = $this->adminToken();

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/admin/evaluation-notifications/send', [
                'subject' => 'Report',
                'message' => 'Body',
                // user_ids missing
            ]);

        $response->assertUnprocessable();
    }

    public function test_send_requires_subject_and_message(): void
    {
        $token = $this->adminToken();
        $data  = $this->createManagerWithSubordinates();

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/admin/evaluation-notifications/send', [
                'user_ids' => [$data['employee']->id],
                // subject + message missing
            ]);

        $response->assertUnprocessable();
    }

    public function test_admin_can_get_notification_history(): void
    {
        $token = $this->adminToken();
        $data  = $this->createManagerWithSubordinates();

        NotificationSend::create([
            'type'           => 'evaluation_report',
            'subject'        => 'Report',
            'message'        => 'Body',
            'recipient_ids'  => [$data['manager']->id],
            'evaluation_ids' => [],
            'status'         => 'sent',
            'sent_by'        => User::where('role', 'admin')->first()->id,
            'sent_at'        => now(),
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/admin/evaluation-notifications/getAll');

        $response->assertOk();
    }

    public function test_preview_with_date_range_filters(): void
    {
        Mail::fake();
        $token = $this->adminToken();
        $data  = $this->createManagerWithSubordinates();

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/admin/evaluation-notifications/preview', [
                'user_ids'   => [$data['employee']->id],
                'subject'    => 'Report',
                'message'    => 'Body',
                'start_date' => now()->subMonth()->toDateString(),
                'end_date'   => now()->toDateString(),
            ]);

        $response->assertOk();
    }

    public function test_send_skips_user_without_manager(): void
    {
        Mail::fake();
        $token    = $this->adminToken();
        $noMgr    = User::factory()->create(['report_to' => null]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/admin/evaluation-notifications/send', [
                'user_ids' => [$noMgr->id],
                'subject'  => 'Report',
                'message'  => 'Body',
            ]);

        $response->assertOk()
            ->assertJsonPath('success_count', 0)
            ->assertJsonPath('skipped_count', 1);

        Mail::assertNothingSent();
    }
}
