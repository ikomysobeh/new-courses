<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Video;
use App\Models\VideoCategory;
use Illuminate\Database\Seeder;

class VideoSeeder extends Seeder
{
    public function run(): void
    {
        $adminId = User::where('role', 'admin')->value('id');

        if (! $adminId) {
            $this->command?->warn('Admin user not found. Run AdminSeeder first.');
            return;
        }

        $leadershipId   = VideoCategory::where('slug', 'leadership-management')->value('id');
        $communicationId = VideoCategory::where('slug', 'communication-skills')->value('id');
        $salesId        = VideoCategory::where('slug', 'sales-marketing')->value('id');
        $hrId           = VideoCategory::where('slug', 'human-resources')->value('id');
        $financeId      = VideoCategory::where('slug', 'finance-accounting')->value('id');
        $devId          = VideoCategory::where('slug', 'personal-development')->value('id');
        $techId         = VideoCategory::where('slug', 'technical-skills')->value('id');
        $healthId       = VideoCategory::where('slug', 'health-safety')->value('id');
        $complianceId   = VideoCategory::where('slug', 'compliance-regulations')->value('id');

        if (! $leadershipId) {
            $this->command?->warn('VideoCategory records not found. Run VideoCategorySeeder first.');
            return;
        }

        $videos = [
            [
                'name'              => 'Foundations of Leadership',
                'description'       => 'An in-depth look at what it means to lead a team effectively.',
                'file_path'         => 'videos/foundations-of-leadership.mp4',
                'file_size'         => 524288000,
                'duration_seconds'  => 3600,
                'transcode_status'  => 'completed',
                'video_category_id' => $leadershipId,
                'created_by'        => $adminId,
            ],
            [
                'name'              => 'Advanced Communication Techniques',
                'description'       => 'Mastering verbal and non-verbal communication in professional settings.',
                'file_path'         => 'videos/advanced-communication-techniques.mp4',
                'file_size'         => 314572800,
                'duration_seconds'  => 2700,
                'transcode_status'  => 'completed',
                'video_category_id' => $communicationId,
                'created_by'        => $adminId,
            ],
            [
                'name'              => 'Sales Strategy & Closing Deals',
                'description'       => 'Proven sales strategies to increase conversions and build long-term client trust.',
                'file_path'         => 'videos/sales-strategy-closing-deals.mp4',
                'file_size'         => 471859200,
                'duration_seconds'  => 4200,
                'transcode_status'  => 'completed',
                'video_category_id' => $salesId,
                'created_by'        => $adminId,
            ],
            [
                'name'              => 'HR Essentials for Managers',
                'description'       => 'Key HR responsibilities and how managers can support their teams.',
                'file_path'         => 'videos/hr-essentials-for-managers.mp4',
                'file_size'         => 262144000,
                'duration_seconds'  => 2400,
                'transcode_status'  => 'completed',
                'video_category_id' => $hrId,
                'created_by'        => $adminId,
            ],
            [
                'name'              => 'Budgeting & Financial Planning',
                'description'       => 'Understanding departmental budgets and financial forecasting.',
                'file_path'         => 'videos/budgeting-financial-planning.mp4',
                'file_size'         => 367001600,
                'duration_seconds'  => 3300,
                'transcode_status'  => 'completed',
                'video_category_id' => $financeId,
                'created_by'        => $adminId,
            ],
            [
                'name'              => 'Goal Setting & Personal Growth',
                'description'       => 'How to set SMART goals and track your personal development journey.',
                'file_path'         => 'videos/goal-setting-personal-growth.mp4',
                'file_size'         => 209715200,
                'duration_seconds'  => 1800,
                'transcode_status'  => 'completed',
                'video_category_id' => $devId,
                'created_by'        => $adminId,
            ],
            [
                'name'              => 'Cybersecurity Awareness Training',
                'description'       => 'Recognising and preventing common cybersecurity threats in the workplace.',
                'file_path'         => 'videos/cybersecurity-awareness-training.mp4',
                'file_size'         => 419430400,
                'duration_seconds'  => 3900,
                'transcode_status'  => 'completed',
                'video_category_id' => $techId,
                'created_by'        => $adminId,
            ],
            [
                'name'              => 'Workplace Safety & First Aid',
                'description'       => 'Essential safety protocols and basic first aid procedures for the workplace.',
                'file_path'         => 'videos/workplace-safety-first-aid.mp4',
                'file_size'         => 502267904,
                'duration_seconds'  => 4500,
                'transcode_status'  => 'completed',
                'video_category_id' => $healthId,
                'created_by'        => $adminId,
            ],
            [
                'name'              => 'Compliance & Anti-Bribery Training',
                'description'       => 'Understanding legal obligations, anti-bribery laws, and ethical conduct.',
                'file_path'         => 'videos/compliance-anti-bribery-training.mp4',
                'file_size'         => 335544320,
                'duration_seconds'  => 3000,
                'transcode_status'  => 'completed',
                'video_category_id' => $complianceId,
                'created_by'        => $adminId,
            ],
        ];

        foreach ($videos as $video) {
            Video::withTrashed()
                ->updateOrCreate(
                    ['name' => $video['name']],
                    array_merge($video, ['deleted_at' => null])
                );
        }

        $this->command?->info('Video seeded: ' . count($videos) . ' records.');
    }
}
