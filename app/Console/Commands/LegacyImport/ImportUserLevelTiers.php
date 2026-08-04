<?php

namespace App\Console\Commands\LegacyImport;

use App\Models\UserLevel;
use App\Models\UserLevelTier;

class ImportUserLevelTiers extends LegacyImportCommand
{
    protected $signature = 'legacy:import-user-level-tiers';

    protected $description = 'Import user_level_tiers from the legacy database (user_level_id remapped via UserLevel.legacy_id)';

    protected array $levelMap = [];

    protected function legacyTable(): string
    {
        return 'user_level_tiers';
    }

    protected function newModel(): string
    {
        return UserLevelTier::class;
    }

    protected function beforeImport(): void
    {
        $this->levelMap = UserLevel::query()->whereNotNull('legacy_id')->pluck('id', 'legacy_id')->all();
    }

    protected function mapRow(array $old): ?array
    {
        $newLevelId = $this->levelMap[$old['user_level_id']] ?? null;

        if ($newLevelId === null) {
            $this->error("No imported UserLevel for legacy user_level_id={$old['user_level_id']} (tier legacy_id={$old['id']})");

            return null;
        }

        return [
            'legacy_id' => $old['id'],
            'user_level_id' => $newLevelId,
            'tier_name' => $old['tier_name'],
            'tier_order' => $old['tier_order'],
            'created_at' => $old['created_at'],
            'updated_at' => $old['updated_at'],
        ];
    }
}
