<?php

namespace Database\Seeders;

use App\Models\UserLevel;
use App\Models\UserLevelTier;
use Illuminate\Database\Seeder;

class UserLevelTierSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        UserLevelTier::query()->delete();

        $tiersByLevel = [
            'L2PM' => ['Tier 1', 'Tier 2', 'Tier 3'],
            'L1' => ['Tier 1', 'Tier 2', 'Tier 3'],
            'L2' => ['Tier 1', 'Tier 2', 'Tier 3'],
            'L3' => ['Tier 1', 'Tier 2', 'Tier 3'],
            'L4' => ['Tier 1', 'Tier 2', 'Tier 3'],
            'L5' => ['Tier 1', 'Tier 2', 'Tier 3'],
            'L6' => ['tier 1'],
        ];

        foreach ($tiersByLevel as $levelCode => $tiers) {
            $level = UserLevel::query()->where('code', $levelCode)->first();

            if (! $level) {
                continue;
            }

            foreach ($tiers as $index => $tierName) {
                UserLevelTier::updateOrCreate(
                    [
                        'user_level_id' => $level->id,
                        'tier_order' => $index + 1,
                    ],
                    [
                        'tier_name' => $tierName,
                    ]
                );
            }
        }
    }
}
