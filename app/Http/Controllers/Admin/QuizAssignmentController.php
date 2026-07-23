<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreQuizAssignmentRequest;
use App\Http\Resources\Quiz\QuizAssignmentResource;
use App\Models\QuizAssignment;
use App\Services\Quiz\QuizAssignmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class QuizAssignmentController extends Controller
{
    public function __construct(private readonly QuizAssignmentService $assignmentService) {}

    /**
     * List quiz assignments.
     */
    public function getAll(Request $request): AnonymousResourceCollection
    {
        $filters = $request->only(['quiz_id', 'user_id']);

        // Query-string values arrive as strings ("true"/"false"); cast to a real
        // boolean so the Sent / Not-sent filter matches the boolean column.
        if ($request->has('notification_sent')) {
            $filters['notification_sent'] = $request->boolean('notification_sent');
        }

        $assignments = $this->assignmentService->getAssignmentsList($filters);

        return QuizAssignmentResource::collection($assignments)
            ->additional(['cards' => $this->assignmentService->getAdminAssignmentCards()]);
    }

    /**
     * Assign a quiz to one or more users.
     */
    public function create(StoreQuizAssignmentRequest $request): JsonResponse
    {
        $newUserIds = $this->assignmentService->assignQuizToUsers(
            quizId: (int) $request->validated('quiz_id'),
            userIds: $request->validated('user_ids'),
            assignedBy: (int) $request->user()->id,
        );

        $assignments = QuizAssignment::query()
            ->with(['user', 'assigner'])
            ->where('quiz_id', $request->validated('quiz_id'))
            ->whereIn('user_id', $newUserIds)
            ->get();

        return QuizAssignmentResource::collection($assignments)
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Remove a quiz assignment.
     */
    public function delete(int $id): JsonResponse
    {
        $this->assignmentService->removeAssignment($id);

        return response()->json(['message' => 'Quiz assignment deleted successfully.']);
    }
}
