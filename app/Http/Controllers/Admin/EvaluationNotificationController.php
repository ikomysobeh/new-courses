<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Evaluation\SendEvaluationNotificationRequest;
use App\Http\Resources\Evaluation\EvaluationNotificationHistoryResource;
use App\Http\Resources\Evaluation\EvaluationNotificationPreviewResource;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use App\Services\Evaluation\Notification\EvaluationNotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EvaluationNotificationController extends Controller
{
    public function __construct(private readonly EvaluationNotificationService $service) {}

    public function getAll(Request $request): AnonymousResourceCollection
    {
        $history = $this->service->getNotificationHistory($request->only(['start_date', 'end_date']));

        return EvaluationNotificationHistoryResource::collection($history);
    }

    public function preview(SendEvaluationNotificationRequest $request): EvaluationNotificationPreviewResource
    {
        $preview = $this->service->previewNotification(
            $request->input('user_ids', []),
            $request->only(['start_date', 'end_date'])
        );

        return new EvaluationNotificationPreviewResource($preview);
    }

    public function send(SendEvaluationNotificationRequest $request): JsonResponse
    {
        $result = $this->service->sendNotifications(
            userIds: $request->input('user_ids', []),
            filters: $request->only(['start_date', 'end_date']),
            subject: $request->validated('subject', 'Evaluation Report'),
            message: $request->validated('message', ''),
            sentBy:  auth()->id(),
        );

        return response()->json($result);
    }
}
