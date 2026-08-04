<?php

namespace App\Console\Commands\LegacyImport;

use App\Models\Evaluation;
use App\Models\NotificationSend;
use App\Models\User;

class ImportNotificationSends extends LegacyImportCommand
{
    protected $signature = 'legacy:import-notification-sends';

    protected $description = "Import notification_templates -> notification_sends. The real data lives inside the old content JSON blob, not the mostly-empty flat columns (notification_type/target_manager_level/employee_count/department_name/email_subject/sent_by are empty in the current data). type/subject/sent_by pulled from content. recipient_ids matched from manager_emails (comma-separated) against users.email. employee_ids best-effort matched from content.employee_names against users.name (client decision - ~88% match rate, unmatched names are simply left out). evaluation_ids remapped, dropping any that reference an evaluation no longer in the legacy DB. No draft rows exist in the current data, so nothing is skipped for that reason.";

    protected array $userMap = [];

    protected array $evaluationMap = [];

    protected array $userIdByLowerName = [];

    protected array $userIdByLowerEmail = [];

    protected function legacyTable(): string
    {
        return 'notification_templates';
    }

    protected function newModel(): string
    {
        return NotificationSend::class;
    }

    protected function beforeImport(): void
    {
        $this->userMap = User::query()->whereNotNull('legacy_id')->pluck('id', 'legacy_id')->all();
        $this->evaluationMap = Evaluation::query()->whereNotNull('legacy_id')->pluck('id', 'legacy_id')->all();

        foreach (User::query()->select(['id', 'name', 'email'])->get() as $user) {
            $this->userIdByLowerName[mb_strtolower($user->name)] = $user->id;
            $this->userIdByLowerEmail[mb_strtolower($user->email)] = $user->id;
        }
    }

    protected function mapRow(array $old): ?array
    {
        $content = json_decode((string) $old['content'], true) ?? [];

        $newSentBy = null;
        $legacySentBy = $old['sent_by'] ?? $content['created_by'] ?? null;

        if ($legacySentBy !== null) {
            $newSentBy = $this->userMap[$legacySentBy] ?? null;

            if ($newSentBy === null) {
                $this->error("No imported User for legacy sent_by/created_by={$legacySentBy} (notification_template legacy_id={$old['id']})");

                return null;
            }
        }

        $recipientIds = [];

        foreach (array_filter(array_map('trim', explode(',', (string) $old['manager_emails']))) as $email) {
            $recipientIds[] = $this->userIdByLowerEmail[mb_strtolower($email)] ?? null;
        }
        $recipientIds = array_values(array_unique(array_filter($recipientIds)));

        $employeeIds = [];

        foreach ($content['employee_names'] ?? [] as $name) {
            $employeeIds[] = $this->userIdByLowerName[mb_strtolower($name)] ?? null;
        }
        $employeeIds = array_values(array_unique(array_filter($employeeIds)));

        $evaluationIds = [];

        foreach ($content['evaluation_ids'] ?? [] as $legacyEvalId) {
            if (isset($this->evaluationMap[$legacyEvalId])) {
                $evaluationIds[] = $this->evaluationMap[$legacyEvalId];
            }
        }

        return [
            'legacy_id' => $old['id'],
            'type' => $content['type'] ?? $old['notification_type'] ?: 'unknown',
            'subject' => $content['email_subject'] ?: ($old['email_subject'] ?: $old['name']),
            'message' => $content['custom_message'] ?: $old['name'],
            'recipient_ids' => $recipientIds,
            'employee_ids' => $employeeIds,
            'evaluation_ids' => $evaluationIds,
            'status' => $old['status'],
            'sent_by' => $newSentBy,
            'sent_at' => $old['sent_at'],
            'created_at' => $old['created_at'],
            'updated_at' => $old['updated_at'],
        ];
    }
}
