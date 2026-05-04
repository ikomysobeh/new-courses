<?php

namespace Database\Seeders;

use App\Models\UserLevel;
use Illuminate\Database\Seeder;

class UserLevelSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        UserLevel::query()->delete();

        $levels = [
            ['code' => 'L2PM', 'name' => 'Project Manager', 'hierarchy_level' => 0, 'can_manage_levels' => []],
            ['code' => 'L1', 'name' => 'Employee', 'hierarchy_level' => 1, 'can_manage_levels' => []],
            ['code' => 'L2', 'name' => 'Direct Manager', 'hierarchy_level' => 2, 'can_manage_levels' => ['L1']],
            ['code' => 'L3', 'name' => 'Senior Manager', 'hierarchy_level' => 3, 'can_manage_levels' => ['L1', 'L2']],
            ['code' => 'L4', 'name' => 'Director', 'hierarchy_level' => 4, 'can_manage_levels' => ['L1', 'L2', 'L3']],
            ['code' => 'L5', 'name' => 'President', 'hierarchy_level' => 5, 'can_manage_levels' => ['L4', 'L3']],
            ['code' => 'L6', 'name' => 'Business Owner', 'hierarchy_level' => 6, 'can_manage_levels' => ['L4', 'L5', 'L3']],
        ];

        foreach ($levels as $level) {
            UserLevel::updateOrCreate(
                ['code' => $level['code']],
                $level
            );
        }
    }
}
