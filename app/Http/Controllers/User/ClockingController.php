<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\ClockInRequest;
use App\Http\Requests\User\ClockOutRequest;
use App\Http\Resources\Course\ClockingResource;
use App\Services\Course\ClockingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ClockingController extends Controller
{
    public function __construct(private readonly ClockingService $clockingService) {}

    /** Clock in the authenticated user (optionally tied to a course). */
    public function clockIn(ClockInRequest $request): JsonResponse
    {
        $clocking = $this->clockingService->clockIn(
            user: $request->user(),
            courseId: $request->validated('course_id'),
        );

        return (new ClockingResource($clocking))
            ->response()
            ->setStatusCode(201);
    }

    /** Clock out the authenticated user's open session. */
    public function clockOut(ClockOutRequest $request): ClockingResource
    {
        $validated = $request->validated();
        $clocking  = $this->clockingService->clockOut(
            user: $request->user(),
            rating: $validated['rating'] ?? null,
            comment: $validated['comment'] ?? null,
        );

        return new ClockingResource($clocking);
    }

    /** Get the authenticated user's clocking history (paginated). */
    public function history(Request $request): AnonymousResourceCollection
    {
        $history = $this->clockingService->getUserHistory($request->user());

        return ClockingResource::collection($history);
    }

    /** Get the authenticated user's currently open clocking session, or null. */
    public function active(Request $request): JsonResponse
    {
        $session = $this->clockingService->getActiveSession($request->user());

        if ($session === null) {
            return response()->json(['data' => null]);
        }

        return (new ClockingResource($session))->response();
    }
}
