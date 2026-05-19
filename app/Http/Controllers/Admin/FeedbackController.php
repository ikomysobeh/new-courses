<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\RespondFeedbackRequest;
use App\Http\Requests\Admin\UpdateFeedbackStatusRequest;
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
        $filters = $request->only(['status', 'type', 'user_id', 'search']);

        return FeedbackResource::collection($this->service->getAllForAdmin($filters));
    }

    public function getById(int $id): FeedbackResource
    {
        return new FeedbackResource($this->service->getById($id));
    }

    public function respond(RespondFeedbackRequest $request, int $id): FeedbackResource
    {
        return new FeedbackResource(
            $this->service->respond($id, $request->validated('admin_response'), $request->validated('status'))
        );
    }

    public function status(UpdateFeedbackStatusRequest $request, int $id): FeedbackResource
    {
        return new FeedbackResource(
            $this->service->updateStatus($id, $request->validated('status'))
        );
    }
}
