<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreBlogPostRequest;
use App\Http\Requests\Admin\UpdateBlogPostRequest;
use App\Http\Resources\Blog\BlogPostResource;
use App\Http\Resources\Blog\BlogPostCardResource;
use App\Models\PodcastPost;
use App\Services\Blog\Media\BlogMediaService;
use App\Services\Blog\Post\BlogPostService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class BlogPostController extends Controller
{
    public function __construct(
        private readonly BlogPostService  $postService,
        private readonly BlogMediaService $mediaService,
    ) {}

    public function getAll(Request $request): AnonymousResourceCollection
    {
        $posts = $this->postService->getAllForAdmin($request->query());

        return BlogPostResource::collection($posts);
    }

    public function create(StoreBlogPostRequest $request): JsonResponse
    {
        $post = $this->postService->createPost(
            $request->validated(),
            (int) $request->user()->id
        );

        return (new BlogPostResource($post->load('creator')))
            ->response()
            ->setStatusCode(201);
    }

    public function getById(int $id): BlogPostResource
    {
        $post = $this->postService->getByIdForAdmin($id);

        return new BlogPostResource($post);
    }

    public function update(UpdateBlogPostRequest $request, int $id): BlogPostResource
    {
        $post = PodcastPost::findOrFail($id);
        $post = $this->postService->updatePost($post, $request->validated());

        return new BlogPostResource($post->load('creator'));
    }

    public function delete(int $id): JsonResponse
    {
        $post = PodcastPost::findOrFail($id);
        $this->postService->deletePost($post);

        return response()->json(['message' => 'Blog post deleted successfully.']);
    }

    public function availableVideos(): JsonResponse
    {
        $videos = $this->mediaService->getAvailableVideos();

        return response()->json(['data' => $videos]);
    }

    public function availableAudios(): JsonResponse
    {
        $audios = $this->mediaService->getAvailableAudios();

        return response()->json(['data' => $audios]);
    }
}
