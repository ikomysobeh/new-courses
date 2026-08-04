<?php

namespace App\Console\Commands\LegacyImport;

use App\Models\User;
use App\Models\UserLevelTier;

class ImportUsers extends LegacyImportCommand
{
    protected $signature = 'legacy:import-users';

    protected $description = 'Import users from the legacy database (department_id passes through as-is; user_level_tier_id remapped via UserLevelTier.legacy_id)';

    protected array $tierMap = [];

    protected function legacyTable(): string
    {
        return 'users';
    }

    protected function newModel(): string
    {
        return User::class;
    }

    protected function beforeImport(): void
    {
        $this->tierMap = UserLevelTier::query()->whereNotNull('legacy_id')->pluck('id', 'legacy_id')->all();
    }

    protected function mapRow(array $old): ?array
    {
        $newTierId = null;

        if ($old['user_level_tier_id'] !== null) {
            $newTierId = $this->tierMap[$old['user_level_tier_id']] ?? null;

            if ($newTierId === null) {
                $this->error("No imported UserLevelTier for legacy user_level_tier_id={$old['user_level_tier_id']} (user legacy_id={$old['id']})");

                return null;
            }
        }

        return [
            'legacy_id' => $old['id'],
            'name' => $old['name'],
            'email' => $old['email'],
            'password' => $old['password'],
            'role' => $old['role'],
            'department_id' => $old['department_id'],
            'user_level_tier_id' => $newTierId,
            'login_token' => $old['login_token'],
            'login_token_expires_at' => $old['login_token_expires_at'],
            'created_at' => $old['created_at'],
            'updated_at' => $old['updated_at'],
        ];
    }
}
