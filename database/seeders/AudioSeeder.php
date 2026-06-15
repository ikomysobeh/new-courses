<?php

namespace Database\Seeders;

use App\Models\Audio;
use App\Models\AudioCategory;
use Illuminate\Database\Seeder;

class AudioSeeder extends Seeder
{
    public function run(): void
    {
        $leadershipId    = AudioCategory::where('slug', 'leadership-management')->value('id');
        $communicationId = AudioCategory::where('slug', 'communication-skills')->value('id');
        $salesId         = AudioCategory::where('slug', 'sales-marketing')->value('id');
        $hrId            = AudioCategory::where('slug', 'human-resources')->value('id');
        $financeId       = AudioCategory::where('slug', 'finance-accounting')->value('id');
        $devId           = AudioCategory::where('slug', 'personal-development')->value('id');
        $techId          = AudioCategory::where('slug', 'technical-skills')->value('id');
        $healthId        = AudioCategory::where('slug', 'health-wellness')->value('id');

        if (! $leadershipId) {
            $this->command?->warn('AudioCategory records not found. Run AudioCategorySeeder first.');
            return;
        }

        $audios = [
            [
                'name'              => 'Introduction to Leadership Principles',
                'description'       => 'Covers the core fundamentals of effective leadership in modern organisations.',
                'local_path'        => null,
                'duration'          => 1800,
                'audio_category_id' => $leadershipId,
            ],
            [
                'name'              => 'Effective Communication in the Workplace',
                'description'       => 'Techniques for clear and assertive communication with teams and management.',
                'local_path'        => null,
                'duration'          => 2400,
                'audio_category_id' => $communicationId,
            ],
            [
                'name'              => 'Sales Fundamentals & Customer Engagement',
                'description'       => 'Understanding the sales cycle and building strong client relationships.',
                'local_path'        => null,
                'duration'          => 3000,
                'audio_category_id' => $salesId,
            ],
            [
                'name'              => 'HR Policies & Employee Relations',
                'description'       => 'An overview of HR best practices, policies, and employee rights.',
                'local_path'        => null,
                'duration'          => 2700,
                'audio_category_id' => $hrId,
            ],
            [
                'name'              => 'Financial Literacy for Employees',
                'description'       => 'Basic financial concepts every employee should understand.',
                'local_path'        => null,
                'duration'          => 2100,
                'audio_category_id' => $financeId,
            ],
            [
                'name'              => 'Time Management & Productivity',
                'description'       => 'Strategies to manage your workday efficiently and maximise output.',
                'local_path'        => null,
                'duration'          => 1500,
                'audio_category_id' => $devId,
            ],
            [
                'name'              => 'Introduction to Data Security',
                'description'       => 'Basics of protecting company data and recognising cybersecurity threats.',
                'local_path'        => null,
                'duration'          => 2200,
                'audio_category_id' => $techId,
            ],
            [
                'name'              => 'Workplace Wellbeing & Stress Management',
                'description'       => 'Practical tips for maintaining mental health and work-life balance.',
                'local_path'        => null,
                'duration'          => 1950,
                'audio_category_id' => $healthId,
            ],
        ];

        foreach ($audios as $audio) {
            Audio::withTrashed()
                ->updateOrCreate(
                    ['name' => $audio['name']],
                    array_merge($audio, ['deleted_at' => null])
                );
        }

        $this->command?->info('Audio seeded: ' . count($audios) . ' records.');
    }
}
