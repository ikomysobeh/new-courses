<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ManualGradeAnswerRequest;
use App\Http\Resources\Quiz\QuizAnswerAdminResource;
use App\Models\QuizAnswer;
use App\Services\Quiz\QuizGradingService;

class QuizAnswerController extends Controller
{
    public function __construct(private readonly QuizGradingService $gradingService) {}

    /**
     * Manually grade a text quiz answer.
     */
    public function grade(ManualGradeAnswerRequest $request, int $answerId): QuizAnswerAdminResource
    {
        $answer = QuizAnswer::query()->with('question')->findOrFail($answerId);

        $this->gradingService->manualGradeAnswer($answerId, $request->validated('points_earned'));

        return new QuizAnswerAdminResource($answer->fresh('question'));
    }
}
