<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Tier IDs from user_level_tiers:
     *  L2PM T1=1  T2=2  T3=3
     *  L1   T1=4  T2=5  T3=6
     *  L2   T1=7  T2=8  T3=9
     *  L3   T1=10 T2=11 T3=12
     *  L4   T1=13 T2=14 T3=15
     *  L5   T1=16 T2=17 T3=18
     *  L6   T1=19
     *
     * Department IDs (key ones):
     *  Real Estate=1, Finance=2, Logistics=3, Marketing=4, T&I=5, Pizza=6
     *  Builders Dept=7, Management Dept=8, 3D Design=9
     *  Pizza Finance=10, RE Finance=11
     *  Logistics Dispatch Teams=12, Logistics HR=13, Logistics Hiring=14
     *  NVT=15, Dispatch 1st=17, Dispatch 2nd=18, Dispatch 3rd=19
     *  Pizza Hiring=20, Pizza Auditors=21, Pizza Operations=22
     *  Ops 2nd=23, Ops 3rd=24, Pizza Maintenance=25
     *  Pizza Project Managers=26, Mangos=27, Pizza Acquisition=28
     *  NVT Marketing=29, Swaida Marketing=30, Logistics Ops Mgmt=31
     *  Project Managers=32, Ops EVS=33, EVS=34
     *  Pizza Employee Obsession=35
     *  Front-end Team=45, Back-end Team=46, BI Team=47
     *  Pizza Screen Project=48
     */
    public function run(): void
    {
        $password = Hash::make('User@12345');
        $adminId  = User::where('email', 'admin@newproject.test')->value('id');

        if (! $adminId) {
            $this->command?->warn('Admin user not found. Run AdminSeeder first.');
            return;
        }

        // ----------------------------------------------------------------
        // Define users: manager_email resolved in a second pass
        // ----------------------------------------------------------------
        $definitions = [

            // ── L5  Presidents  (report to admin / L6) ───────────────────
            [
                'name'                => 'Ahmed Hassan',
                'email'               => 'ahmed.hassan@company.test',
                'role'                => 'user',
                'department_id'       => 2,   // Finance
                'user_level_tier_id'  => 16,  // L5 T1
                'manager_email'       => 'admin@newproject.test',
            ],
            [
                'name'                => 'Sara Al-Farsi',
                'email'               => 'sara.alfarsi@company.test',
                'role'                => 'user',
                'department_id'       => 1,   // Real Estate
                'user_level_tier_id'  => 16,  // L5 T1
                'manager_email'       => 'admin@newproject.test',
            ],
            [
                'name'                => 'Omar Khalil',
                'email'               => 'omar.khalil@company.test',
                'role'                => 'user',
                'department_id'       => 6,   // Pizza (Operations group)
                'user_level_tier_id'  => 17,  // L5 T2
                'manager_email'       => 'admin@newproject.test',
            ],

            // ── L4  Directors  ────────────────────────────────────────────
            [
                'name'                => 'Kareem Mansour',
                'email'               => 'kareem.mansour@company.test',
                'role'                => 'user',
                'department_id'       => 10,  // Pizza Finance
                'user_level_tier_id'  => 13,  // L4 T1
                'manager_email'       => 'ahmed.hassan@company.test',
            ],
            [
                'name'                => 'Nadia Yousef',
                'email'               => 'nadia.yousef@company.test',
                'role'                => 'user',
                'department_id'       => 8,   // RE Management Dept
                'user_level_tier_id'  => 13,  // L4 T1
                'manager_email'       => 'sara.alfarsi@company.test',
            ],
            [
                'name'                => 'Rami Saleh',
                'email'               => 'rami.saleh@company.test',
                'role'                => 'user',
                'department_id'       => 22,  // Pizza Operations
                'user_level_tier_id'  => 14,  // L4 T2
                'manager_email'       => 'omar.khalil@company.test',
            ],
            [
                'name'                => 'Lina Abdo',
                'email'               => 'lina.abdo@company.test',
                'role'                => 'user',
                'department_id'       => 12,  // Logistics Dispatch Teams
                'user_level_tier_id'  => 13,  // L4 T1
                'manager_email'       => 'omar.khalil@company.test',
            ],
            [
                'name'                => 'Faisal Nasser',
                'email'               => 'faisal.nasser@company.test',
                'role'                => 'user',
                'department_id'       => 5,   // T&I
                'user_level_tier_id'  => 14,  // L4 T2
                'manager_email'       => 'admin@newproject.test',
            ],

            // ── L3  Senior Managers  ──────────────────────────────────────
            [
                'name'                => 'Maya Haddad',
                'email'               => 'maya.haddad@company.test',
                'role'                => 'user',
                'department_id'       => 22,  // Pizza Operations
                'user_level_tier_id'  => 10,  // L3 T1
                'manager_email'       => 'rami.saleh@company.test',
            ],
            [
                'name'                => 'Tarek Bitar',
                'email'               => 'tarek.bitar@company.test',
                'role'                => 'user',
                'department_id'       => 31,  // Logistics Ops Mgmt
                'user_level_tier_id'  => 10,  // L3 T1
                'manager_email'       => 'lina.abdo@company.test',
            ],
            [
                'name'                => 'Hana Zreik',
                'email'               => 'hana.zreik@company.test',
                'role'                => 'user',
                'department_id'       => 11,  // RE Finance
                'user_level_tier_id'  => 11,  // L3 T2
                'manager_email'       => 'kareem.mansour@company.test',
            ],
            [
                'name'                => 'Sami Nassar',
                'email'               => 'sami.nassar@company.test',
                'role'                => 'user',
                'department_id'       => 46,  // Back-end Team
                'user_level_tier_id'  => 10,  // L3 T1
                'manager_email'       => 'faisal.nasser@company.test',
            ],
            [
                'name'                => 'Rana Khoury',
                'email'               => 'rana.khoury@company.test',
                'role'                => 'user',
                'department_id'       => 45,  // Front-end Team
                'user_level_tier_id'  => 10,  // L3 T1
                'manager_email'       => 'faisal.nasser@company.test',
            ],
            [
                'name'                => 'Joe Barakat',
                'email'               => 'joe.barakat@company.test',
                'role'                => 'user',
                'department_id'       => 7,   // Builders Department
                'user_level_tier_id'  => 11,  // L3 T2
                'manager_email'       => 'nadia.yousef@company.test',
            ],
            [
                'name'                => 'Layla Mrad',
                'email'               => 'layla.mrad@company.test',
                'role'                => 'user',
                'department_id'       => 13,  // Logistics HR Team
                'user_level_tier_id'  => 11,  // L3 T2
                'manager_email'       => 'lina.abdo@company.test',
            ],

            // ── L2PM  Project Managers  ───────────────────────────────────
            [
                'name'                => 'Ali Moussa',
                'email'               => 'ali.moussa@company.test',
                'role'                => 'user',
                'department_id'       => 48,  // Pizza Screen Project
                'user_level_tier_id'  => 1,   // L2PM T1
                'manager_email'       => 'rami.saleh@company.test',
            ],
            [
                'name'                => 'Nour Jaber',
                'email'               => 'nour.jaber@company.test',
                'role'                => 'user',
                'department_id'       => 26,  // Pizza Project Managers
                'user_level_tier_id'  => 2,   // L2PM T2
                'manager_email'       => 'rami.saleh@company.test',
            ],
            [
                'name'                => 'Chadi Frem',
                'email'               => 'chadi.frem@company.test',
                'role'                => 'user',
                'department_id'       => 47,  // BI Team
                'user_level_tier_id'  => 1,   // L2PM T1
                'manager_email'       => 'faisal.nasser@company.test',
            ],

            // ── L2  Direct Managers  ──────────────────────────────────────
            [
                'name'                => 'Khalid Hassan',
                'email'               => 'khalid.hassan@company.test',
                'role'                => 'user',
                'department_id'       => 23,  // Ops 2nd Shift
                'user_level_tier_id'  => 7,   // L2 T1
                'manager_email'       => 'maya.haddad@company.test',
            ],
            [
                'name'                => 'Dana Salam',
                'email'               => 'dana.salam@company.test',
                'role'                => 'user',
                'department_id'       => 24,  // Ops 3rd Shift
                'user_level_tier_id'  => 7,   // L2 T1
                'manager_email'       => 'maya.haddad@company.test',
            ],
            [
                'name'                => 'Yusuf Aoun',
                'email'               => 'yusuf.aoun@company.test',
                'role'                => 'user',
                'department_id'       => 17,  // Dispatch 1st Shift
                'user_level_tier_id'  => 8,   // L2 T2
                'manager_email'       => 'tarek.bitar@company.test',
            ],
            [
                'name'                => 'Leen Azar',
                'email'               => 'leen.azar@company.test',
                'role'                => 'user',
                'department_id'       => 18,  // Dispatch 2nd Shift
                'user_level_tier_id'  => 7,   // L2 T1
                'manager_email'       => 'tarek.bitar@company.test',
            ],
            [
                'name'                => 'Mariam Faris',
                'email'               => 'mariam.faris@company.test',
                'role'                => 'user',
                'department_id'       => 32,  // Project Managers (Finance)
                'user_level_tier_id'  => 8,   // L2 T2
                'manager_email'       => 'hana.zreik@company.test',
            ],
            [
                'name'                => 'Ziad Kanaan',
                'email'               => 'ziad.kanaan@company.test',
                'role'                => 'user',
                'department_id'       => 7,   // Builders Department
                'user_level_tier_id'  => 9,   // L2 T3
                'manager_email'       => 'joe.barakat@company.test',
            ],
            [
                'name'                => 'Tony Saad',
                'email'               => 'tony.saad@company.test',
                'role'                => 'user',
                'department_id'       => 8,   // Management Department
                'user_level_tier_id'  => 7,   // L2 T1
                'manager_email'       => 'joe.barakat@company.test',
            ],
            [
                'name'                => 'Rola Haidar',
                'email'               => 'rola.haidar@company.test',
                'role'                => 'user',
                'department_id'       => 46,  // Back-end Team
                'user_level_tier_id'  => 7,   // L2 T1
                'manager_email'       => 'sami.nassar@company.test',
            ],
            [
                'name'                => 'Mazen Ghazi',
                'email'               => 'mazen.ghazi@company.test',
                'role'                => 'user',
                'department_id'       => 45,  // Front-end Team
                'user_level_tier_id'  => 8,   // L2 T2
                'manager_email'       => 'rana.khoury@company.test',
            ],
            [
                'name'                => 'Dina Turk',
                'email'               => 'dina.turk@company.test',
                'role'                => 'user',
                'department_id'       => 14,  // Logistics Hiring
                'user_level_tier_id'  => 7,   // L2 T1
                'manager_email'       => 'layla.mrad@company.test',
            ],

            // ── L1  Employees  ────────────────────────────────────────────
            [
                'name'                => 'Hassan Jomaa',
                'email'               => 'hassan.jomaa@company.test',
                'role'                => 'user',
                'department_id'       => 42,  // Pizza Ops Group 1 2nd
                'user_level_tier_id'  => 4,   // L1 T1
                'manager_email'       => 'khalid.hassan@company.test',
            ],
            [
                'name'                => 'Mona Assi',
                'email'               => 'mona.assi@company.test',
                'role'                => 'user',
                'department_id'       => 42,
                'user_level_tier_id'  => 4,
                'manager_email'       => 'khalid.hassan@company.test',
            ],
            [
                'name'                => 'Karim Fayyad',
                'email'               => 'karim.fayyad@company.test',
                'role'                => 'user',
                'department_id'       => 43,  // Pizza Ops Group 2 2nd
                'user_level_tier_id'  => 5,   // L1 T2
                'manager_email'       => 'khalid.hassan@company.test',
            ],
            [
                'name'                => 'Aya Hamdan',
                'email'               => 'aya.hamdan@company.test',
                'role'                => 'user',
                'department_id'       => 37,  // Pizza Ops Group 1 3rd
                'user_level_tier_id'  => 4,
                'manager_email'       => 'dana.salam@company.test',
            ],
            [
                'name'                => 'Wael Daher',
                'email'               => 'wael.daher@company.test',
                'role'                => 'user',
                'department_id'       => 38,  // Pizza Ops Group 2 3rd
                'user_level_tier_id'  => 4,
                'manager_email'       => 'dana.salam@company.test',
            ],
            [
                'name'                => 'Joelle Rizk',
                'email'               => 'joelle.rizk@company.test',
                'role'                => 'user',
                'department_id'       => 17,  // Dispatch 1st
                'user_level_tier_id'  => 5,
                'manager_email'       => 'yusuf.aoun@company.test',
            ],
            [
                'name'                => 'Peter Frem',
                'email'               => 'peter.frem@company.test',
                'role'                => 'user',
                'department_id'       => 17,
                'user_level_tier_id'  => 4,
                'manager_email'       => 'yusuf.aoun@company.test',
            ],
            [
                'name'                => 'Nada Riad',
                'email'               => 'nada.riad@company.test',
                'role'                => 'user',
                'department_id'       => 18,  // Dispatch 2nd
                'user_level_tier_id'  => 4,
                'manager_email'       => 'leen.azar@company.test',
            ],
            [
                'name'                => 'George Hanna',
                'email'               => 'george.hanna@company.test',
                'role'                => 'user',
                'department_id'       => 46,  // Back-end
                'user_level_tier_id'  => 4,
                'manager_email'       => 'rola.haidar@company.test',
            ],
            [
                'name'                => 'Rim Nassar',
                'email'               => 'rim.nassar@company.test',
                'role'                => 'user',
                'department_id'       => 46,
                'user_level_tier_id'  => 5,
                'manager_email'       => 'rola.haidar@company.test',
            ],
            [
                'name'                => 'Taline Mkrtchian',
                'email'               => 'taline.mkrtchian@company.test',
                'role'                => 'user',
                'department_id'       => 45,  // Front-end
                'user_level_tier_id'  => 4,
                'manager_email'       => 'mazen.ghazi@company.test',
            ],
            [
                'name'                => 'Elie Khoury',
                'email'               => 'elie.khoury@company.test',
                'role'                => 'user',
                'department_id'       => 45,
                'user_level_tier_id'  => 5,
                'manager_email'       => 'mazen.ghazi@company.test',
            ],
            [
                'name'                => 'Samar Wehbe',
                'email'               => 'samar.wehbe@company.test',
                'role'                => 'user',
                'department_id'       => 7,   // Builders
                'user_level_tier_id'  => 4,
                'manager_email'       => 'ziad.kanaan@company.test',
            ],
            [
                'name'                => 'Fadi Zeidan',
                'email'               => 'fadi.zeidan@company.test',
                'role'                => 'user',
                'department_id'       => 7,
                'user_level_tier_id'  => 6,   // L1 T3
                'manager_email'       => 'ziad.kanaan@company.test',
            ],
            [
                'name'                => 'Carol Frem',
                'email'               => 'carol.frem@company.test',
                'role'                => 'user',
                'department_id'       => 9,   // 3D Design
                'user_level_tier_id'  => 4,
                'manager_email'       => 'tony.saad@company.test',
            ],
            [
                'name'                => 'Jad Nassar',
                'email'               => 'jad.nassar@company.test',
                'role'                => 'user',
                'department_id'       => 32,  // Project Managers (Finance)
                'user_level_tier_id'  => 4,
                'manager_email'       => 'mariam.faris@company.test',
            ],
            [
                'name'                => 'Lara Gerges',
                'email'               => 'lara.gerges@company.test',
                'role'                => 'user',
                'department_id'       => 14,  // Logistics Hiring
                'user_level_tier_id'  => 5,
                'manager_email'       => 'dina.turk@company.test',
            ],
            [
                'name'                => 'Ramzi Doumit',
                'email'               => 'ramzi.doumit@company.test',
                'role'                => 'user',
                'department_id'       => 14,
                'user_level_tier_id'  => 4,
                'manager_email'       => 'dina.turk@company.test',
            ],
        ];

        // ── Pass 1: create / update all users (without report_to) ─────────
        foreach ($definitions as $def) {
            User::updateOrCreate(
                ['email' => $def['email']],
                [
                    'name'               => $def['name'],
                    'password'           => $password,
                    'role'               => $def['role'],
                    'department_id'      => $def['department_id'],
                    'user_level_tier_id' => $def['user_level_tier_id'],
                ]
            );
        }

        // ── Pass 2: set report_to now that all users exist ────────────────
        $emailToId = User::whereIn('email', array_column($definitions, 'email'))
            ->orWhere('email', 'admin@newproject.test')
            ->pluck('id', 'email');

        foreach ($definitions as $def) {
            $managerId = $emailToId[$def['manager_email']] ?? null;

            if ($managerId) {
                User::where('email', $def['email'])
                    ->update(['report_to' => $managerId]);
            }
        }

        $count = count($definitions);
        $this->command?->info("User seeded: {$count} users (password: User@12345).");
    }
}
