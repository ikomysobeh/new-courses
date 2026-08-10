<?php

namespace App\Services\AttentionScore;

use App\Models\AttentionScoreConfig;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class AttentionScoreConfigService
{
    private const CACHE_KEY = 'attention_score_config.active';

    public function getActiveConfig(): AttentionScoreConfig
    {
        // Cache only the scalar ID, never the Eloquent model — caching a full
        // model risks __PHP_Incomplete_Class on unserialize if the cache entry
        // outlives a deploy/autoload change. The actual model fetch below is
        // a trivial primary-key lookup, so this stays cheap either way.
        $id = Cache::rememberForever(self::CACHE_KEY, function () {
            $config = AttentionScoreConfig::active()->latest('id')->first();

            if (!$config) {
                throw new \RuntimeException('No active attention score config found. Run the seeding migration.');
            }

            return $config->id;
        });

        $config = AttentionScoreConfig::find($id);

        if (!$config) {
            Cache::forget(self::CACHE_KEY);
            return $this->getActiveConfig();
        }

        return $config;
    }

    public function saveNewConfig(array $data, ?User $createdBy = null): AttentionScoreConfig
    {
        $this->validateConfig($data['config']);

        return DB::transaction(function () use ($data, $createdBy) {
            AttentionScoreConfig::where('is_active', true)->update(['is_active' => false]);

            $config = AttentionScoreConfig::create([
                'name'       => $data['name'],
                'is_active'  => true,
                'config'     => $data['config'],
                'created_by' => $createdBy?->id,
            ]);

            Cache::forget(self::CACHE_KEY);

            return $config;
        });
    }

    public function getConfigHistory()
    {
        return AttentionScoreConfig::orderByDesc('id')->get();
    }

    public function restoreConfig(int $configId, ?User $createdBy = null): AttentionScoreConfig
    {
        $old = AttentionScoreConfig::findOrFail($configId);

        return $this->saveNewConfig([
            'name'   => $old->name . ' (restored)',
            'config' => $old->config,
        ], $createdBy);
    }

    /**
     * Validates a config's internal consistency: weights sum correctly,
     * bands cover their domain with no gaps/overlaps, thresholds ascend,
     * and every adjustment/penalty is a non-positive number.
     */
    public function validateConfig(array $config): void
    {
        $video = $config['video'] ?? null;

        if (!$video) {
            throw new InvalidArgumentException('Config is missing the "video" section.');
        }

        $weights = $video['weights'] ?? [];
        $weightSum = (float) ($weights['watch_time'] ?? 0)
            + (float) ($weights['engagement'] ?? 0)
            + (float) ($weights['completion'] ?? 0);

        if (abs($weightSum - 100.0) > 0.001) {
            throw new InvalidArgumentException("Video component weights must sum to 100, got {$weightSum}.");
        }

        $this->assertMinMaxBandsCoverDomain($video['time_ratio_bands'] ?? [], 'time_ratio_bands');
        $this->assertMinMaxBandsCoverDomain($video['speed_change_bands'] ?? [], 'speed_change_bands');
        $this->assertMinMaxBandsCoverDomain($video['completion_bands'] ?? [], 'completion_bands');
        $this->assertAscendingThresholdBands($video['skip_ratio_bands'] ?? [], 'skip_ratio_bands');

        foreach (($video['speed_change_bands'] ?? []) as $band) {
            $this->assertNonPositive($band['adjustment'] ?? 0, 'speed_change_bands.adjustment');
        }
        foreach (($video['skip_ratio_bands'] ?? []) as $band) {
            $this->assertNonPositive($band['adjustment'] ?? 0, 'skip_ratio_bands.adjustment');
        }

        $consistency = $video['consistency_validation'] ?? [];
        $this->assertNonPositive($consistency['penalty'] ?? 0, 'consistency_validation.penalty');

        $riskLevels = $config['risk_levels'] ?? [];
        if (($riskLevels['high_below'] ?? 0) >= ($riskLevels['medium_below'] ?? 0)) {
            throw new InvalidArgumentException('risk_levels.high_below must be less than risk_levels.medium_below.');
        }

        $blended = $config['blended_score_weights'] ?? [];
        $blendedSum = (float) ($blended['completion'] ?? 0)
            + (float) ($blended['progress'] ?? 0)
            + (float) ($blended['attention'] ?? 0)
            + (float) ($blended['quiz'] ?? 0);

        if (abs($blendedSum - 1.0) > 0.001) {
            throw new InvalidArgumentException("blended_score_weights must sum to 1.0, got {$blendedSum}.");
        }
    }

    /**
     * Bands with {min, max, ...} must fully cover the domain with no gaps
     * or overlaps: each band's max must equal the next band's min, and the
     * last band's max must be null (open-ended).
     */
    private function assertMinMaxBandsCoverDomain(array $bands, string $label): void
    {
        if (empty($bands)) {
            throw new InvalidArgumentException("{$label} must not be empty.");
        }

        usort($bands, fn ($a, $b) => $a['min'] <=> $b['min']);

        for ($i = 0; $i < count($bands) - 1; $i++) {
            if ((float) $bands[$i]['max'] !== (float) $bands[$i + 1]['min']) {
                throw new InvalidArgumentException("{$label} has a gap/overlap between band {$i} and " . ($i + 1) . '.');
            }
        }

        if ($bands[count($bands) - 1]['max'] !== null) {
            throw new InvalidArgumentException("{$label} last band must have max = null (open-ended).");
        }
    }

    /**
     * Bands with {max, adjustment} only (implicit min = previous max) must
     * ascend strictly and end with max = null.
     */
    private function assertAscendingThresholdBands(array $bands, string $label): void
    {
        if (empty($bands)) {
            throw new InvalidArgumentException("{$label} must not be empty.");
        }

        $previous = -INF;
        foreach ($bands as $i => $band) {
            $max = $band['max'];
            if ($max === null) {
                if ($i !== count($bands) - 1) {
                    throw new InvalidArgumentException("{$label} only the last band may have max = null.");
                }
                continue;
            }
            if ((float) $max <= $previous) {
                throw new InvalidArgumentException("{$label} thresholds must strictly ascend.");
            }
            $previous = (float) $max;
        }
    }

    private function assertNonPositive(mixed $value, string $label): void
    {
        if ((float) $value > 0) {
            throw new InvalidArgumentException("{$label} must be <= 0, got {$value}.");
        }
    }
}
