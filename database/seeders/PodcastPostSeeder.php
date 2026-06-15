<?php

namespace Database\Seeders;

use App\Models\PodcastPost;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class PodcastPostSeeder extends Seeder
{
    public function run(): void
    {
        $adminId = User::where('role', 'admin')->value('id');

        if (! $adminId) {
            $this->command?->warn('Admin user not found. Run AdminSeeder first.');
            return;
        }

        $posts = [
            [
                'title'        => 'Building a Learning Culture in Your Organisation',
                'excerpt'      => 'Discover how forward-thinking companies embed continuous learning into everyday work life.',
                'description'  => '<p>A true learning culture goes beyond mandatory training. In this post we explore the frameworks, habits, and leadership behaviours that transform organisations into environments where every employee grows every day.</p><p>We look at real case studies, practical strategies, and how L&D teams can measure the ROI of a culture shift.</p>',
                'status'       => 'published',
                'published_at' => Carbon::now()->subDays(7),
                'tags'         => ['learning culture', 'L&D', 'leadership', 'employee development'],
            ],
            [
                'title'        => 'The Science of Effective Corporate Training',
                'excerpt'      => 'What does neuroscience tell us about how adults really learn? The answers might surprise you.',
                'description'  => '<p>Traditional lecture-based training is one of the least effective ways to change behaviour. This article dives into cognitive load theory, spaced repetition, and retrieval practice — and how to apply them in your training programmes.</p>',
                'status'       => 'published',
                'published_at' => Carbon::now()->subDays(14),
                'tags'         => ['neuroscience', 'adult learning', 'training design', 'spaced repetition'],
            ],
            [
                'title'        => 'Podcast Episode: Leadership in Times of Change',
                'excerpt'      => 'In this episode, we speak to a seasoned executive about navigating organisational transformation.',
                'description'  => '<p>Change is the only constant in modern business. In this podcast episode, our guest shares how they led their team through a major digital transformation — the challenges, the lessons, and what they would do differently.</p>',
                'status'       => 'published',
                'published_at' => Carbon::now()->subDays(3),
                'tags'         => ['podcast', 'leadership', 'change management', 'digital transformation'],
            ],
            [
                'title'        => '5 Ways to Boost Employee Engagement Through Training',
                'excerpt'      => 'Engaged employees learn faster and retain more. Here\'s how to connect training to motivation.',
                'description'  => '<p>Employee engagement and learning effectiveness are deeply connected. When employees feel their development matters, they invest more in training outcomes. This post outlines five proven strategies to make your training programmes more engaging and impactful.</p>',
                'status'       => 'published',
                'published_at' => Carbon::now()->subDays(21),
                'tags'         => ['employee engagement', 'training', 'motivation', 'HR'],
            ],
            [
                'title'        => 'Podcast Episode: Mental Health & Wellbeing at Work',
                'excerpt'      => 'A candid conversation about the importance of psychological safety and mental health support in the workplace.',
                'description'  => '<p>Mental health is no longer a taboo topic in the workplace. In this episode we speak with a wellbeing specialist about practical steps managers and HR teams can take to create psychologically safe, supportive environments.</p>',
                'status'       => 'published',
                'published_at' => Carbon::now()->subDays(1),
                'tags'         => ['podcast', 'mental health', 'wellbeing', 'psychological safety'],
            ],
            [
                'title'        => 'How Online Learning is Reshaping Corporate Education',
                'excerpt'      => 'The shift to digital learning platforms has accelerated. What does this mean for L&D teams?',
                'description'  => '<p>The pandemic was a catalyst that forced organisations to move training online almost overnight. Years later, the landscape has shifted permanently. This post examines the trends, tools, and best practices shaping the future of online corporate learning.</p>',
                'status'       => 'draft',
                'published_at' => null,
                'tags'         => ['e-learning', 'LMS', 'online training', 'digital learning'],
            ],
        ];

        foreach ($posts as $post) {
            $slug = Str::slug($post['title']);

            // Ensure unique slug
            $existingCount = PodcastPost::where('slug', 'LIKE', $slug . '%')
                ->where('created_by', $adminId)
                ->count();

            if ($existingCount > 0) {
                $slug = $slug . '-' . ($existingCount + 1);
            }

            PodcastPost::updateOrCreate(
                ['title' => $post['title'], 'created_by' => $adminId],
                [
                    'slug'         => $slug,
                    'excerpt'      => $post['excerpt'],
                    'description'  => $post['description'],
                    'status'       => $post['status'],
                    'published_at' => $post['published_at'],
                    'tags'         => json_encode($post['tags']),
                    'created_by'   => $adminId,
                ]
            );
        }

        $this->command?->info('PodcastPost seeded: ' . count($posts) . ' posts.');
    }
}
