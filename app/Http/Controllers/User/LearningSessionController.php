<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\OnlineCourse\EndSessionRequest;
use App\Http\Requests\User\OnlineCourse\StartSessionRequest;
use App\Http\Requests\User\OnlineCourse\UpdateSessionProgressRequest;
use App\Http\Resources\User\OnlineCourse\SessionEndResource;
use App\Http\Resources\User\OnlineCourse\SessionStartResource;
use App\Services\OnlineCourse\User\LearningSessionService;

class LearningSessionController extends Controller
{
    public function __construct(private LearningSessionService $service) {}

    public function start(StartSessionRequest $request)
    {
        $result = $this->service->startSession(
            auth()->id(),
            $request->course_online_id,
            $request->content_id,
            $request->content_type
        );

        return new SessionStartResource($result);
    }

    public function progress(UpdateSessionProgressRequest $request, int $sessionId)
    {
        $this->service->updateProgress($sessionId, auth()->id(), $request->validated());

        return response()->json(['ok' => true]);
    }

    public function end(EndSessionRequest $request, int $sessionId)
    {
        $result = $this->service->endSession($sessionId, auth()->id(), $request->validated());

        return new SessionEndResource($result);
    }
}
