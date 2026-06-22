<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PostInteractionSeeder extends Seeder
{
    public function run(): void
    {
        $posts = DB::table('podcast_posts')->where('status', 'published')->get();
        $users = DB::table('users')->get();

        if ($posts->isEmpty() || $users->isEmpty()) {
            $this->command?->warn('PostInteractionSeeder: no published posts or users — skipping.');
            return;
        }

        $commentTemplates = [
            'Very insightful content! I learned a lot from this episode.',
            'Could you do a follow-up on this topic? I would love more depth.',
            'Great production quality — the practical examples were especially helpful.',
            'This helped me understand the concept much more clearly. Thank you!',
            'I shared this with my team and everyone found it valuable.',
            'The speaker explained everything clearly. Highly recommended.',
            'Looking forward to more episodes like this one.',
            'This is exactly what I needed for my current project at work.',
            'Excellent content! The real-world case studies were particularly useful.',
            'Glad this was covered — I had the same question for a long time.',
        ];

        $commentRows = 0;
        $likeRows    = 0;

        foreach ($posts as $postIdx => $post) {
            // 3–6 comments per post, cycling through users deterministically
            $commentCount = ($postIdx % 4) + 3; // 3–6
            $commentUsers = $users->values()->filter(
                fn ($u, $i) => ($i + $postIdx) % max(1, $users->count()) < $commentCount
            );

            if ($commentUsers->isEmpty()) {
                $commentUsers = $users->take($commentCount);
            }

            foreach ($commentUsers->values() as $cIdx => $user) {
                $body = $commentTemplates[($postIdx * 3 + $cIdx) % count($commentTemplates)];

                DB::table('post_comments')->insert([
                    'podcast_post_id' => $post->id,
                    'user_id'         => $user->id,
                    'body'            => $body,
                    'created_at'      => now()->subDays(rand(1, 20)),
                    'updated_at'      => now()->subDays(rand(1, 20)),
                ]);
                $commentRows++;
            }

            // 40–70% of users like each post
            $likeCount = (int) ($users->count() * (40 + ($postIdx * 7 % 31)) / 100);
            $likeUsers = $users->values()->filter(fn ($u, $i) => $i < $likeCount);

            foreach ($likeUsers as $user) {
                $exists = DB::table('post_likes')
                    ->where('podcast_post_id', $post->id)
                    ->where('user_id', $user->id)
                    ->exists();

                if (! $exists) {
                    DB::table('post_likes')->insert([
                        'podcast_post_id' => $post->id,
                        'user_id'         => $user->id,
                        'created_at'      => now()->subDays(rand(1, 20)),
                        'updated_at'      => now()->subDays(rand(1, 20)),
                    ]);
                    $likeRows++;
                }
            }
        }

        $this->command?->info("PostInteractionSeeder: {$commentRows} comments, {$likeRows} likes across {$posts->count()} posts.");
    }
}
