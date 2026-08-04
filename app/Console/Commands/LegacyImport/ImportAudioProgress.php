<?php

namespace App\Console\Commands\LegacyImport;

use App\Models\Audio;
use App\Models\AudioProgress;
use App\Models\User;

class ImportAudioProgress extends LegacyImportCommand
{
    protected $signature = 'legacy:import-audio-progress';

    protected $description = 'Import audio_progress - near 1:1.';

    protected array $audioMap = [];

    protected array $userMap = [];

    protected function legacyTable(): string
    {
        return 'audio_progress';
    }

    protected function newModel(): string
    {
        return AudioProgress::class;
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

        if ($newAudioId === null || $newUserId === null) {
            $this->error("Unresolved mapping for audio_progress legacy_id={$old['id']} (audio_id={$old['audio_id']}, user_id={$old['user_id']})");

            return null;
        }

        return [
            'legacy_id' => $old['id'],
            'user_id' => $newUserId,
            'audio_id' => $newAudioId,
            'current_time' => $old['current_time'],
            'total_listened_time' => $old['total_listened_time'],
            'is_completed' => $old['is_completed'],
            'completion_percentage' => $old['completion_percentage'],
            'last_accessed_at' => $old['last_accessed_at'],
            'created_at' => $old['created_at'],
            'updated_at' => $old['updated_at'],
        ];
    }
}
