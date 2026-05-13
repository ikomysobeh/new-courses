<?php

namespace App\Services\Evaluation\Config;

use App\Models\EvaluationConfig;
use App\Models\EvaluationHistory;
use App\Models\EvaluationType;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\ValidationException;

class EvaluationConfigService
{
    public function getAllConfigs(): Collection
    {
        return EvaluationConfig::with('types')->orderBy('name')->get();
    }

    public function createConfig(array $data): EvaluationConfig
    {
        return EvaluationConfig::create($data);
    }

    public function updateConfig(int $id, array $data): EvaluationConfig
    {
        $config = EvaluationConfig::findOrFail($id);
        $config->update($data);
        return $config->fresh('types');
    }

    public function deleteConfig(int $id): void
    {
        $config = EvaluationConfig::findOrFail($id);

        // Guard: history rows snapshot config name — if any exist, block deletion
        $inUse = EvaluationHistory::where('config_name', $config->name)->exists();
        if ($inUse) {
            throw ValidationException::withMessages([
                'id' => ['Cannot delete this config — it has been used in evaluation history records.'],
            ]);
        }

        $config->delete();
    }

    public function addType(int $configId, array $data): EvaluationType
    {
        // Verify config exists
        EvaluationConfig::findOrFail($configId);

        return EvaluationType::create(array_merge($data, ['evaluation_config_id' => $configId]));
    }

    public function updateType(int $id, array $data): EvaluationType
    {
        $type = EvaluationType::findOrFail($id);
        $type->update($data);
        return $type->fresh();
    }

    public function deleteType(int $id): void
    {
        EvaluationType::findOrFail($id)->delete();
    }
}
