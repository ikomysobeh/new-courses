<?php

namespace Database\Seeders;

use App\Models\EvaluationConfig;
use App\Models\EvaluationType;
use Illuminate\Database\Seeder;

class EvaluationConfigSeeder extends Seeder
{
    public function run(): void
    {
        $configs = [
            [
                'name'       => 'Course Completion Evaluation',
                'max_score'  => 100,
                'applies_to' => 'regular',
                'types' => [
                    ['type_name' => 'Excellent',    'score_value' => 100],
                    ['type_name' => 'Very Good',    'score_value' => 85],
                    ['type_name' => 'Good',         'score_value' => 70],
                    ['type_name' => 'Satisfactory', 'score_value' => 55],
                    ['type_name' => 'Needs Improvement', 'score_value' => 40],
                ],
            ],
            [
                'name'       => 'Online Course Evaluation',
                'max_score'  => 100,
                'applies_to' => 'online',
                'types' => [
                    ['type_name' => 'Outstanding', 'score_value' => 100],
                    ['type_name' => 'Proficient',  'score_value' => 80],
                    ['type_name' => 'Developing',  'score_value' => 60],
                    ['type_name' => 'Beginning',   'score_value' => 40],
                ],
            ],
            [
                'name'       => 'General Training Evaluation',
                'max_score'  => 100,
                'applies_to' => 'both',
                'types' => [
                    ['type_name' => 'Exceeded Expectations', 'score_value' => 100],
                    ['type_name' => 'Met Expectations',      'score_value' => 75],
                    ['type_name' => 'Below Expectations',    'score_value' => 50],
                    ['type_name' => 'Unsatisfactory',        'score_value' => 25],
                ],
            ],
        ];

        foreach ($configs as $configData) {
            $types = $configData['types'];
            unset($configData['types']);

            $config = EvaluationConfig::updateOrCreate(
                ['name' => $configData['name']],
                [
                    'max_score'  => $configData['max_score'],
                    'applies_to' => $configData['applies_to'],
                ]
            );

            foreach ($types as $typeData) {
                EvaluationType::updateOrCreate(
                    [
                        'evaluation_config_id' => $config->id,
                        'type_name'            => $typeData['type_name'],
                    ],
                    ['score_value' => $typeData['score_value']]
                );
            }
        }

        $this->command?->info('EvaluationConfig seeded: ' . count($configs) . ' configs with their types.');
    }
}
