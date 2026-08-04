<?php

namespace App\Console\Commands\LegacyImport;

use App\Models\EvaluationConfig;

class ImportEvaluationConfigs extends LegacyImportCommand
{
    protected $signature = 'legacy:import-evaluation-configs';

    protected $description = 'Import evaluation_configs - near 1:1.';

    protected function legacyTable(): string
    {
        return 'evaluation_configs';
    }

    protected function newModel(): string
    {
        return EvaluationConfig::class;
    }

    protected function mapRow(array $old): ?array
    {
        return [
            'legacy_id' => $old['id'],
            'name' => $old['name'],
            'max_score' => $old['max_score'],
            'applies_to' => $old['applies_to'],
            'created_at' => $old['created_at'],
            'updated_at' => $old['updated_at'],
        ];
    }
}
