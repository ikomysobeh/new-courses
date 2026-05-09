<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\Quiz\QuizAttemptAdminResource;
use App\Models\QuizAttempt;
use App\Services\Quiz\QuizAttemptService;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class QuizAttemptController extends Controller
{
    public function __construct(private readonly QuizAttemptService $attemptService) {}

    /**
     * List all attempts for a given quiz.
     */
    public function getAll(int $quizId): AnonymousResourceCollection
    {
        $attempts = QuizAttempt::query()
            ->with('user')
            ->where('quiz_id', $quizId)
            ->orderByDesc('id')
            ->get();

        return QuizAttemptAdminResource::collection($attempts)
            ->additional(['cards' => $this->attemptService->getAdminAttemptCards($quizId)]);
    }

    /**
     * Get a single attempt with all answers.
     */
    public function getById(int $quizId, int $attemptId): QuizAttemptAdminResource
    {
        $attempt = QuizAttempt::query()
            ->with(['user', 'answers.question'])
            ->where('id', $attemptId)
            ->where('quiz_id', $quizId)
            ->firstOrFail();

        return new QuizAttemptAdminResource($attempt);
    }
}
