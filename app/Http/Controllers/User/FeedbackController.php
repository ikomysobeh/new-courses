<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\StoreFeedbackRequest;
use App\Http\Resources\Support\FeedbackResource;
use App\Services\Support\FeedbackService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class FeedbackController extends Controller
{
    public function __construct(private readonly FeedbackService $service) {}

    public function getAll(Request $request): AnonymousResourceCollection
    {
        $filters = $request->only(['status', 'type']);

        return FeedbackResource::collection(
            $this->service->getForUser(auth()->id(), $filters)
        );
    }

    public function create(StoreFeedbackRequest $request): JsonResponse
    {
        $feedback = $this->service->submitFeedback(auth()->id(), $request->validated());

        return (new FeedbackResource($feedback))
            ->response()
            ->setStatusCode(201);
    }

    public function getById(int $id): FeedbackResource
    {
        $feedback = $this->service->getById($id);

        abort_if($feedback->user_id !== auth()->id(), 403, 'Access denied.');

        return new FeedbackResource($feedback);
    }
}
