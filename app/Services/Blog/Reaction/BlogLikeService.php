<?php

namespace App\Services\Blog\Reaction;

use App\Models\PostLike;
use App\Models\PodcastPost;
use App\Models\User;

class BlogLikeService
{
    public function like(PodcastPost $post, User $user): PostLike
    {
        return PostLike::firstOrCreate([
            'podcast_post_id' => $post->id,
            'user_id'         => $user->id,
        ]);
    }

    public function unlike(PodcastPost $post, User $user): void
    {
        PostLike::where('podcast_post_id', $post->id)
            ->where('user_id', $user->id)
            ->delete();
    }

    public function getLikeCount(PodcastPost $post): int
    {
        return $post->likes()->count();
    }

    public function getLikeStateForUser(PodcastPost $post, User $user): bool
    {
        return PostLike::where('podcast_post_id', $post->id)
            ->where('user_id', $user->id)
            ->exists();
    }
}
