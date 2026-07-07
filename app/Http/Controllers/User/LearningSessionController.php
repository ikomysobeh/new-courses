<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\OnlineCourse\EndSessionRequest;
use App\Http\Requests\User\OnlineCourse\StartSessionRequest;
use App\Http\Requests\User\OnlineCourse\UpdateSessionProgressRequest;
use App\Http\Resources\User\OnlineCourse\SessionEndResource;
use App\Http\Resources\User\OnlineCourse\SessionStartResource;
use App\Services\OnlineCourse\User\LearningSessionService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class LearningSessionController extends Controller
{
    public function __construct(private LearningSessionService $service) {}


    public function start(StartSessionRequest $request)
    {
        DB::enableQueryLog();

        $result = $this->service->startSession(
            auth()->id(),
            $request->course_online_id,
            $request->content_id,
            $request->content_type
        );
        $queries = DB::getQueryLog();
        Log::info('queries log', [$queries]);
        return new SessionStartResource($result);
    }

    public function progress(UpdateSessionProgressRequest $request, int $sessionId)
    {

        Log::info('LEARNING_SESSION_PROGRESS_CALLED', [
        'user_id' => auth()->id(),
        'session_id' => $sessionId,
        'timestamp' => now()->toIso8601String(),
        'ip' => $request->ip(),
        'user_agent' => $request->header('User-Agent'),
    ]);
        $this->service->updateProgress($sessionId, auth()->id(), $request->validated());
        // ADD THIS LOG (success)
    Log::info('LEARNING_SESSION_PROGRESS_SUCCESS', [
        'user_id' => auth()->id(),
        'session_id' => $sessionId,
        'resume_position' => $request->resume_position,
        'is_completed' => $request->is_completed,
    ]);
        return response()->json(['ok' => true]);
    }

    public function end(EndSessionRequest $request, int $sessionId)
    {
        Log::info('LEARNING_SESSION_END_CALLED', [
        'user_id' => auth()->id(),
        'session_id' => $sessionId,
        'timestamp' => now()->toIso8601String(),
        'ip' => $request->ip(),
        'user_agent' => $request->header('User-Agent'),
    ]);
        $result = $this->service->endSession($sessionId, auth()->id(), $request->validated());
        // ADD THIS LOG (success)
    Log::info('LEARNING_SESSION_END_SUCCESS', [
        'user_id' => auth()->id(),
        'session_id' => $sessionId,
        'is_completed' => $request->is_completed,
    ]);
        return new SessionEndResource($result);
    }
}
