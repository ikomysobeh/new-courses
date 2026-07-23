<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\SubmitQuizAnswersRequest;
use App\Http\Resources\Quiz\UserQuizAttemptResource;
use App\Http\Resources\Quiz\UserQuizResource;
use App\Services\Quiz\QuizAttemptService;
use App\Services\Quiz\QuizService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class QuizController extends Controller
{
    public function __construct(
        private readonly QuizService $quizService,
        private readonly QuizAttemptService $attemptService,
    ) {}

    /**
     * Get all quizzes assigned to the authenticated user.
     */
    public function getAll(Request $request): AnonymousResourceCollection
    {
        $quizzes = $this->quizService->getAllForUser($request->user()->id);

        return UserQuizResource::collection($quizzes);
    }

    /**
     * Get quiz details with questions (no correct answers exposed).
     */
    public function getById(Request $request, int $id): UserQuizResource
    {
        $quiz = $this->quizService->getById($id, $request->user()->id);

        return new UserQuizResource($quiz);
    }

    /**
     * Start a new quiz attempt.
     */
    public function start(Request $request, int $id): JsonResponse
    {
        $attempt = $this->attemptService->startAttempt($request->user()->id, $id);

        return (new UserQuizAttemptResource($attempt))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Submit answers for an in-progress attempt.
     */
    public function submit(SubmitQuizAnswersRequest $request, int $id, int $attemptId): UserQuizAttemptResource
    {
        $attempt = $this->attemptService->submitAttempt(
            attemptId: $attemptId,
            userId: $request->user()->id,
            answers: $request->validated('answers'),
        );

        return new UserQuizAttemptResource($attempt);
    }

    /**
     * Get the result of a completed attempt.
     */
    public function result(Request $request, int $id, int $attemptId): UserQuizAttemptResource
    {
        $attempt = $this->attemptService->getAttemptResult($attemptId, $request->user()->id);

        return new UserQuizAttemptResource($attempt);
    }
}
