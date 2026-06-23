<?php

namespace App\Http\Controllers;

use App\Http\Resources\Blog\BlogPostCardResource;
use App\Http\Resources\Blog\BlogPostDetailResource;
use App\Services\Blog\Media\BlogMediaService;
use App\Services\Blog\Post\BlogPostService;
use App\Services\Blog\Reaction\BlogLikeService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class BlogFeedController extends Controller
{
    public function __construct(
        private readonly BlogPostService  $postService,
        private readonly BlogMediaService $mediaService,
        private readonly BlogLikeService  $likeService,
    ) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $perPage = (int) $request->query('per_page', 15);
        $posts   = $this->postService->getPublicFeed($perPage);

        return BlogPostCardResource::collection($posts);
    }

    public function show(Request $request, string $slug): BlogPostDetailResource
    {
        $post      = $this->postService->getPostBySlug($slug);
        $streamUrl   = $this->mediaService->resolveStreamUrl($post->mediable_type, $post->mediable_id);
        $subtitleUrl = $this->mediaService->resolveSubtitleUrl($post->mediable_type, $post->mediable_id);
        $isLiked     = $request->user()
            ? $this->likeService->getLikeStateForUser($post, $request->user())
            : null;

        return new BlogPostDetailResource($post, $streamUrl, $isLiked, $subtitleUrl);
    }
}
