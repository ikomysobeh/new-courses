<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\User;
use App\Models\UserLevel;
use App\Models\UserLevelTier;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $defaultPassword = env('ADMIN_INITIAL_PASSWORD', 'Admin@12345');

        $businessOwnerTierId = UserLevelTier::query()
            ->whereHas('userLevel', function ($query) {
                $query->where('code', 'L6');
            })
            ->where('tier_order', 1)
            ->value('id');

        if (! $businessOwnerTierId) {
            $businessOwnerLevelId = UserLevel::query()
                ->where('code', 'L6')
                ->value('id');

            if ($businessOwnerLevelId) {
                $businessOwnerTierId = UserLevelTier::query()->updateOrCreate(
                    [
                        'user_level_id' => $businessOwnerLevelId,
                        'tier_order' => 1,
                    ],
                    [
                        'tier_name' => 'Tier 1',
                    ]
                )->id;
            }
        }

        $rootDepartmentId = Department::query()
            ->whereNull('parent_id')
            ->orderBy('id')
            ->value('id');

        User::query()->updateOrCreate(
            ['email' => 'admin@newproject.test'],
            [
                'name' => 'Admin User',
                'password' => Hash::make($defaultPassword),
                'role' => 'admin',
                'department_id' => $rootDepartmentId,
                'user_level_tier_id' => $businessOwnerTierId,
                'report_to' => null,
            ]
        );

        $this->command?->info('Admin user seeded: admin@newproject.test');
        $this->command?->warn('Initial admin password is from ADMIN_INITIAL_PASSWORD env value.');
    }
}
