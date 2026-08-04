<?php

namespace App\Console\Commands\LegacyImport;

use App\Models\UserLevel;
use Illuminate\Console\Command;

class ImportUserLevels extends LegacyImportCommand
{
    protected $signature = 'legacy:import-user-levels';

    protected $description = 'Import user_levels from the legacy database (matched by code, since old/new ids differ)';

    protected function legacyTable(): string
    {
        return 'user_levels';
    }

    protected function newModel(): string
    {
        return UserLevel::class;
    }

    protected function mapRow(array $old): array
    {
        return [
            'legacy_id' => $old['id'],
            'code' => $old['code'],
            'name' => $old['name'],
            'hierarchy_level' => $old['hierarchy_level'],
            'can_manage_levels' => json_decode((string) $old['can_manage_levels'], true) ?? [],
            'created_at' => $old['created_at'],
            'updated_at' => $old['updated_at'],
        ];
    }
}
