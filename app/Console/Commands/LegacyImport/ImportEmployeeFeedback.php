<?php

namespace App\Console\Commands\LegacyImport;

use App\Models\EmployeeFeedback;
use App\Models\User;

class ImportEmployeeFeedback extends LegacyImportCommand
{
    protected $signature = 'legacy:import-employee-feedback';

    protected $description = 'Import employee_feedback - identical schema, just remaps user_id.';

    protected array $userMap = [];

    protected function legacyTable(): string
    {
        return 'employee_feedback';
    }

    protected function newModel(): string
    {
        return EmployeeFeedback::class;
    }

    protected function beforeImport(): void
    {
        $this->userMap = User::query()->whereNotNull('legacy_id')->pluck('id', 'legacy_id')->all();
    }

    protected function mapRow(array $old): ?array
    {
        $newUserId = $this->userMap[$old['user_id']] ?? null;

        if ($newUserId === null) {
            $this->error("No imported User for legacy user_id={$old['user_id']} (employee_feedback legacy_id={$old['id']})");

            return null;
        }

        return [
            'legacy_id' => $old['id'],
            'user_id' => $newUserId,
            'type' => $old['type'],
            'title' => $old['title'],
            'description' => $old['description'],
            'status' => $old['status'],
            'admin_response' => $old['admin_response'],
            'created_at' => $old['created_at'],
            'updated_at' => $old['updated_at'],
        ];
    }
}
