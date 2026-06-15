<?php

namespace Database\Seeders;

use App\Models\VideoCategory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class VideoCategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['name' => 'Leadership & Management', 'sort_order' => 1],
            ['name' => 'Communication Skills',     'sort_order' => 2],
            ['name' => 'Sales & Marketing',         'sort_order' => 3],
            ['name' => 'Human Resources',           'sort_order' => 4],
            ['name' => 'Finance & Accounting',      'sort_order' => 5],
            ['name' => 'Personal Development',      'sort_order' => 6],
            ['name' => 'Technical Skills',          'sort_order' => 7],
            ['name' => 'Health & Safety',           'sort_order' => 8],
            ['name' => 'Compliance & Regulations',  'sort_order' => 9],
        ];

        foreach ($categories as $cat) {
            VideoCategory::updateOrCreate(
                ['slug' => Str::slug($cat['name'])],
                [
                    'name'       => $cat['name'],
                    'sort_order' => $cat['sort_order'],
                ]
            );
        }

        $this->command?->info('VideoCategory seeded: ' . count($categories) . ' records.');
    }
}
