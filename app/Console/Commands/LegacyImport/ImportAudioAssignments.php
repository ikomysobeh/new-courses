<?php

namespace App\Console\Commands\LegacyImport;

use App\Models\Audio;
use App\Models\AudioAssignment;
use App\Models\User;

class ImportAudioAssignments extends LegacyImportCommand
{
    protected $signature = 'legacy:import-audio-assignments';

    protected $description = 'Import audio_assignments. Drops started_at/completed_at/status/progress_percentage - redundant with audio_progress (imported next), which already tracks per-user completion/progress directly.';

    protected array $audioMap = [];

    protected array $userMap = [];

    protected function legacyTable(): string
    {
        return 'audio_assignments';
    }

    protected function newModel(): string
    {
        return AudioAssignment::class;
    }

    protected function beforeImport(): void
    {
        $this->audioMap = Audio::query()->whereNotNull('legacy_id')->pluck('id', 'legacy_id')->all();
        $this->userMap = User::query()->whereNotNull('legacy_id')->pluck('id', 'legacy_id')->all();
    }

    protected function mapRow(array $old): ?array
    {
        $newAudioId = $this->audioMap[$old['audio_id']] ?? null;
        $newUserId = $this->userMap[$old['user_id']] ?? null;
        $newAssignedBy = $this->userMap[$old['assigned_by']] ?? null;

        if ($newAudioId === null || $newUserId === null || $newAssignedBy === null) {
            $this->error("Unresolved mapping for audio_assignment legacy_id={$old['id']} (audio_id={$old['audio_id']}, user_id={$old['user_id']}, assigned_by={$old['assigned_by']})");

            return null;
        }

        return [
            'legacy_id' => $old['id'],
            'audio_id' => $newAudioId,
            'user_id' => $newUserId,
            'assigned_by' => $newAssignedBy,
            'assigned_at' => $old['assigned_at'],
            'notification_sent' => $old['notification_sent'],
            'created_at' => $old['created_at'],
            'updated_at' => $old['updated_at'],
        ];
    }
}
