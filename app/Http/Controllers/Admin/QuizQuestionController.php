<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreQuizQuestionRequest;
use App\Http\Requests\Admin\UpdateQuizQuestionRequest;
use App\Http\Resources\Quiz\QuizQuestionResource;
use App\Models\QuizQuestion;
use App\Services\Quiz\QuizQuestionService;
use Illuminate\Http\JsonResponse;

class QuizQuestionController extends Controller
{
    public function __construct(private readonly QuizQuestionService $questionService) {}

    /**
     * Add a question to a quiz.
     */
    public function create(StoreQuizQuestionRequest $request, int $quizId): JsonResponse
    {
        $question = $this->questionService->addQuestion($quizId, $request->validated());

        return (new QuizQuestionResource($question))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Update a quiz question.
     */
    public function update(UpdateQuizQuestionRequest $request, int $quizId, int $questionId): QuizQuestionResource
    {
        $question = QuizQuestion::query()
            ->where('id', $questionId)
            ->where('quiz_id', $quizId)
            ->firstOrFail();

        $question = $this->questionService->updateQuestion($question, $request->validated());

        return new QuizQuestionResource($question);
    }

    /**
     * Delete a quiz question.
     */
    public function delete(int $quizId, int $questionId): JsonResponse
    {
        $question = QuizQuestion::query()
            ->where('id', $questionId)
            ->where('quiz_id', $quizId)
            ->firstOrFail();

        $this->questionService->deleteQuestion($question);

        return response()->json(['message' => 'Question deleted successfully.']);
    }
}
