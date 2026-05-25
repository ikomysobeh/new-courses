<?php

namespace App\Http\Controllers;

use App\Http\Resources\Blog\BlogLikeResource;
use App\Models\PodcastPost;
use App\Services\Blog\Reaction\BlogLikeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BlogLikeController extends Controller
{
    public function __construct(private readonly BlogLikeService $likeService) {}

    public function like(Request $request, int $postId): JsonResponse
    {
        $post = PodcastPost::published()->findOrFail($postId);
        $this->likeService->like($post, $request->user());

        return response()->json(new BlogLikeResource([
            'like_count' => $this->likeService->getLikeCount($post),
            'is_liked'   => true,
        ]));
    }

    public function unlike(Request $request, int $postId): JsonResponse
    {
        $post = PodcastPost::published()->findOrFail($postId);
        $this->likeService->unlike($post, $request->user());

        return response()->json(new BlogLikeResource([
            'like_count' => $this->likeService->getLikeCount($post),
            'is_liked'   => false,
        ]));
    }
}
