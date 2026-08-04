<?php

namespace App\Console\Commands\LegacyImport;

use App\Models\EvaluationConfig;
use App\Models\EvaluationType;

class ImportEvaluationTypes extends LegacyImportCommand
{
    protected $signature = 'legacy:import-evaluation-types';

    protected $description = 'Import evaluation_types. evaluation_config_id remapped via evaluation_configs.legacy_id.';

    protected array $configMap = [];

    protected function legacyTable(): string
    {
        return 'evaluation_types';
    }

    protected function newModel(): string
    {
        return EvaluationType::class;
    }

    protected function beforeImport(): void
    {
        $this->configMap = EvaluationConfig::query()->whereNotNull('legacy_id')->pluck('id', 'legacy_id')->all();
    }

    protected function mapRow(array $old): ?array
    {
        $newConfigId = $this->configMap[$old['evaluation_config_id']] ?? null;

        if ($newConfigId === null) {
            $this->error("No imported EvaluationConfig for legacy evaluation_config_id={$old['evaluation_config_id']} (type legacy_id={$old['id']})");

            return null;
        }

        return [
            'legacy_id' => $old['id'],
            'evaluation_config_id' => $newConfigId,
            'type_name' => $old['type_name'],
            'score_value' => $old['score_value'],
            'created_at' => $old['created_at'],
            'updated_at' => $old['updated_at'],
        ];
    }
}
