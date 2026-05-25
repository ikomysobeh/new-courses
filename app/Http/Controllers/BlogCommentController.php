<?php

namespace App\Http\Controllers;

use App\Http\Requests\User\StoreBlogCommentRequest;
use App\Http\Resources\Blog\BlogCommentResource;
use App\Models\PodcastPost;
use App\Models\PostComment;
use App\Services\Blog\Comment\BlogCommentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BlogCommentController extends Controller
{
    public function __construct(private readonly BlogCommentService $commentService) {}

    public function store(StoreBlogCommentRequest $request, int $postId): JsonResponse
    {
        $post    = PodcastPost::published()->findOrFail($postId);
        $comment = $this->commentService->addComment($post, $request->user(), $request->validated('body'));

        return (new BlogCommentResource($comment->load('user')))
            ->response()
            ->setStatusCode(201);
    }

    public function destroy(Request $request, int $commentId): JsonResponse
    {
        $comment = PostComment::findOrFail($commentId);
        $this->commentService->deleteComment($comment, $request->user());

        return response()->json(['message' => 'Comment deleted successfully.']);
    }
}
