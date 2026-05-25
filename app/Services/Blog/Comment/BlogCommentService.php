<?php

namespace App\Services\Blog\Comment;

use App\Models\PostComment;
use App\Models\PodcastPost;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Collection;

class BlogCommentService
{
    public function listCommentsForPost(PodcastPost $post): Collection
    {
        return $post->comments()->with('user:id,name')->orderBy('created_at')->get();
    }

    public function addComment(PodcastPost $post, User $user, string $body): PostComment
    {
        return $post->comments()->create([
            'user_id' => $user->id,
            'body'    => $body,
        ]);
    }

    public function deleteComment(PostComment $comment, User $user): void
    {
        if (!$user->isAdmin() && $comment->user_id !== $user->id) {
            throw new AuthorizationException('You are not allowed to delete this comment.');
        }

        $comment->delete();
    }
}
