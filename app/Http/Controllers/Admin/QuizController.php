<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreQuizRequest;
use App\Http\Requests\Admin\UpdateQuizRequest;
use App\Http\Resources\Quiz\QuizResource;
use App\Models\Quiz;
use App\Services\Quiz\QuizQuestionService;
use App\Services\Quiz\QuizService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class QuizController extends Controller
{
    public function __construct(
        private readonly QuizService $quizService,
        private readonly QuizQuestionService $questionService,
    ) {}

    /**
     * List all quizzes for admin.
     */
    public function getAll(Request $request): AnonymousResourceCollection
    {
        $quizzes = $this->quizService->getAllForAdmin($request->only(['status', 'course_id']));

        return QuizResource::collection($quizzes)
            ->additional(['cards' => $this->quizService->getAdminQuizCards()]);
    }

    /**
     * Create a new quiz.
     */
    public function create(StoreQuizRequest $request): JsonResponse
    {
        $quiz = $this->quizService->createQuiz($request->validated());

        return (new QuizResource($quiz))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Get quiz details with questions.
     */
    public function getById(int $id): QuizResource
    {
        $quiz = $this->quizService->getById($id);

        return new QuizResource($quiz);
    }

    /**
     * Update quiz fields.
     */
    public function update(UpdateQuizRequest $request, int $id): QuizResource
    {
        $quiz = Quiz::query()->findOrFail($id);
        $quiz = $this->quizService->updateQuiz($quiz, $request->validated());

        return new QuizResource($quiz);
    }

    /**
     * Soft delete a quiz.
     */
    public function delete(int $id): JsonResponse
    {
        $quiz = Quiz::query()->findOrFail($id);
        $this->quizService->deleteQuiz($quiz);

        return response()->json(['message' => 'Quiz deleted successfully.']);
    }
}
