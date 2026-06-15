<?php

namespace Database\Seeders;

use App\Models\CourseModule;
use App\Models\CourseOnline;
use App\Models\ModuleContent;
use App\Models\User;
use App\Models\Video;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class CourseOnlineSeeder extends Seeder
{
    public function run(): void
    {
        $adminId = User::where('role', 'admin')->value('id');

        if (! $adminId) {
            $this->command?->warn('Admin user not found. Run AdminSeeder first.');
            return;
        }

        // Resolve seeded video IDs for module contents
        $videoIds = Video::whereIn('name', [
            'Foundations of Leadership',
            'Advanced Communication Techniques',
            'Sales Strategy & Closing Deals',
            'HR Essentials for Managers',
            'Budgeting & Financial Planning',
            'Goal Setting & Personal Growth',
            'Cybersecurity Awareness Training',
            'Workplace Safety & First Aid',
            'Compliance & Anti-Bribery Training',
        ])->pluck('id', 'name');

        $courses = [
            [
                'name'               => 'Online Leadership Masterclass',
                'description'        => 'A self-paced online programme covering leadership theory, practical tools, and real-world case studies.',
                'level'              => 'Advanced',
                'estimated_duration' => 180,
                'status'             => 'published',
                'is_active'          => true,
                'deadline'           => Carbon::now()->addMonths(6),
                'modules' => [
                    [
                        'name'               => 'Module 1: Foundations of Leadership',
                        'description'        => 'Explore the core concepts and theories of modern leadership.',
                        'order_number'       => 1,
                        'estimated_duration' => 60,
                        'has_quiz'           => false,
                        'quiz_required'      => false,
                        'contents' => [
                            [
                                'content_type' => 'video',
                                'title'        => 'Foundations of Leadership – Video',
                                'description'  => 'Watch the introductory video on leadership principles.',
                                'order_number' => 1,
                                'video_name'   => 'Foundations of Leadership',
                                'duration'     => 3600,
                                'is_required'  => true,
                                'is_active'    => true,
                            ],
                        ],
                    ],
                    [
                        'name'               => 'Module 2: Advanced Communication for Leaders',
                        'description'        => 'Learn how to communicate effectively as a leader across different situations.',
                        'order_number'       => 2,
                        'estimated_duration' => 60,
                        'has_quiz'           => false,
                        'quiz_required'      => false,
                        'contents' => [
                            [
                                'content_type' => 'video',
                                'title'        => 'Advanced Communication Techniques – Video',
                                'description'  => 'Master communication strategies for leadership effectiveness.',
                                'order_number' => 1,
                                'video_name'   => 'Advanced Communication Techniques',
                                'duration'     => 2700,
                                'is_required'  => true,
                                'is_active'    => true,
                            ],
                        ],
                    ],
                    [
                        'name'               => 'Module 3: Goal Setting & Personal Growth',
                        'description'        => 'Understand how to align personal and team goals with organisational objectives.',
                        'order_number'       => 3,
                        'estimated_duration' => 60,
                        'has_quiz'           => true,
                        'quiz_required'      => true,
                        'contents' => [
                            [
                                'content_type' => 'video',
                                'title'        => 'Goal Setting & Personal Growth – Video',
                                'description'  => 'Learn the SMART framework and growth mindset strategies.',
                                'order_number' => 1,
                                'video_name'   => 'Goal Setting & Personal Growth',
                                'duration'     => 1800,
                                'is_required'  => true,
                                'is_active'    => true,
                            ],
                        ],
                    ],
                ],
            ],
            [
                'name'               => 'Online Sales & Marketing Certification',
                'description'        => 'A comprehensive e-learning course covering the entire sales and marketing lifecycle.',
                'level'              => 'Intermediate',
                'estimated_duration' => 240,
                'status'             => 'published',
                'is_active'          => true,
                'deadline'           => Carbon::now()->addMonths(4),
                'modules' => [
                    [
                        'name'               => 'Module 1: Sales Fundamentals',
                        'description'        => 'Understanding the sales funnel, prospecting, and qualification.',
                        'order_number'       => 1,
                        'estimated_duration' => 90,
                        'has_quiz'           => true,
                        'quiz_required'      => true,
                        'contents' => [
                            [
                                'content_type' => 'video',
                                'title'        => 'Sales Strategy & Closing Deals – Video',
                                'description'  => 'A deep dive into the sales process from start to close.',
                                'order_number' => 1,
                                'video_name'   => 'Sales Strategy & Closing Deals',
                                'duration'     => 4200,
                                'is_required'  => true,
                                'is_active'    => true,
                            ],
                        ],
                    ],
                    [
                        'name'               => 'Module 2: HR & People Management',
                        'description'        => 'Understanding the role of HR in supporting a sales team.',
                        'order_number'       => 2,
                        'estimated_duration' => 60,
                        'has_quiz'           => false,
                        'quiz_required'      => false,
                        'contents' => [
                            [
                                'content_type' => 'video',
                                'title'        => 'HR Essentials for Managers – Video',
                                'description'  => 'Key HR responsibilities relevant to team managers.',
                                'order_number' => 1,
                                'video_name'   => 'HR Essentials for Managers',
                                'duration'     => 2400,
                                'is_required'  => true,
                                'is_active'    => true,
                            ],
                        ],
                    ],
                    [
                        'name'               => 'Module 3: Financial Awareness for Sales Teams',
                        'description'        => 'Budget management and financial literacy for sales professionals.',
                        'order_number'       => 3,
                        'estimated_duration' => 90,
                        'has_quiz'           => false,
                        'quiz_required'      => false,
                        'contents' => [
                            [
                                'content_type' => 'video',
                                'title'        => 'Budgeting & Financial Planning – Video',
                                'description'  => 'How budgets work and what every sales person should know.',
                                'order_number' => 1,
                                'video_name'   => 'Budgeting & Financial Planning',
                                'duration'     => 3300,
                                'is_required'  => true,
                                'is_active'    => true,
                            ],
                        ],
                    ],
                ],
            ],
            [
                'name'               => 'Online Compliance & Safety Course',
                'description'        => 'Mandatory e-learning covering cybersecurity, workplace safety, and legal compliance.',
                'level'              => 'Beginner',
                'estimated_duration' => 150,
                'status'             => 'published',
                'is_active'          => true,
                'deadline'           => null,
                'modules' => [
                    [
                        'name'               => 'Module 1: Cybersecurity Awareness',
                        'description'        => 'Recognise and prevent cybersecurity threats in the workplace.',
                        'order_number'       => 1,
                        'estimated_duration' => 75,
                        'has_quiz'           => true,
                        'quiz_required'      => true,
                        'contents' => [
                            [
                                'content_type' => 'video',
                                'title'        => 'Cybersecurity Awareness Training – Video',
                                'description'  => 'Learn to identify phishing, malware, and data protection risks.',
                                'order_number' => 1,
                                'video_name'   => 'Cybersecurity Awareness Training',
                                'duration'     => 3900,
                                'is_required'  => true,
                                'is_active'    => true,
                            ],
                        ],
                    ],
                    [
                        'name'               => 'Module 2: Workplace Safety',
                        'description'        => 'Core workplace safety protocols and emergency procedures.',
                        'order_number'       => 2,
                        'estimated_duration' => 75,
                        'has_quiz'           => true,
                        'quiz_required'      => true,
                        'contents' => [
                            [
                                'content_type' => 'video',
                                'title'        => 'Workplace Safety & First Aid – Video',
                                'description'  => 'Essential safety procedures, fire safety, and first aid basics.',
                                'order_number' => 1,
                                'video_name'   => 'Workplace Safety & First Aid',
                                'duration'     => 4500,
                                'is_required'  => true,
                                'is_active'    => true,
                            ],
                        ],
                    ],
                ],
            ],
        ];

        foreach ($courses as $courseData) {
            $modules = $courseData['modules'];
            unset($courseData['modules']);

            $course = CourseOnline::withTrashed()->updateOrCreate(
                ['name' => $courseData['name']],
                array_merge($courseData, [
                    'created_by' => $adminId,
                    'deleted_at' => null,
                ])
            );

            foreach ($modules as $moduleData) {
                $contents = $moduleData['contents'];
                unset($moduleData['contents']);

                $module = CourseModule::updateOrCreate(
                    [
                        'course_online_id' => $course->id,
                        'order_number'     => $moduleData['order_number'],
                    ],
                    array_merge($moduleData, ['course_online_id' => $course->id])
                );

                foreach ($contents as $contentData) {
                    $videoId = null;

                    if ($contentData['content_type'] === 'video' && isset($contentData['video_name'])) {
                        $videoId = $videoIds[$contentData['video_name']] ?? null;
                    }

                    unset($contentData['video_name']);

                    ModuleContent::updateOrCreate(
                        [
                            'module_id'    => $module->id,
                            'order_number' => $contentData['order_number'],
                        ],
                        array_merge($contentData, [
                            'module_id' => $module->id,
                            'video_id'  => $videoId,
                        ])
                    );
                }
            }
        }

        $this->command?->info('CourseOnline seeded: ' . count($courses) . ' online courses with modules and contents.');
    }
}
