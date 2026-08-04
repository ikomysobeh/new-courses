<?php

namespace App\Console\Commands\LegacyImport;

use App\Models\ActivityLog;
use App\Models\User;

class ImportActivityLogs extends LegacyImportCommand
{
    protected $signature = 'legacy:import-activity-logs';

    protected $description = "Import activity_logs - near 1:1. model_id remapped via users.legacy_id when model_type is App\Models\User (the only value seen in the current data); other model_type values pass model_id through unmapped since we can't know which table they target.";

    protected array $userMap = [];

    protected function legacyTable(): string
    {
        return 'activity_logs';
    }

    protected function newModel(): string
    {
        return ActivityLog::class;
    }

    protected function beforeImport(): void
    {
        $this->userMap = User::query()->whereNotNull('legacy_id')->pluck('id', 'legacy_id')->all();
    }

    protected function mapRow(array $old): ?array
    {
        $newUserId = null;

        if ($old['user_id'] !== null) {
            $newUserId = $this->userMap[$old['user_id']] ?? null;

            if ($newUserId === null) {
                $this->error("No imported User for legacy user_id={$old['user_id']} (activity_log legacy_id={$old['id']})");

                return null;
            }
        }

        $newModelId = $old['model_id'];

        if ($old['model_type'] === 'App\\Models\\User' && $old['model_id'] !== null) {
            $newModelId = $this->userMap[$old['model_id']] ?? null;

            if ($newModelId === null) {
                $this->error("No imported User for legacy model_id={$old['model_id']} (activity_log legacy_id={$old['id']}, model_type=App\\Models\\User)");

                return null;
            }
        }

        return [
            'legacy_id' => $old['id'],
            'user_id' => $newUserId,
            'description' => $old['description'],
            'action' => $old['action'],
            'model_type' => $old['model_type'],
            'model_id' => $newModelId,
            'properties' => $old['properties'] !== null && $old['properties'] !== '' ? json_decode($old['properties'], true) : null,
            'created_at' => $old['created_at'],
            'updated_at' => $old['updated_at'],
        ];
    }
}
