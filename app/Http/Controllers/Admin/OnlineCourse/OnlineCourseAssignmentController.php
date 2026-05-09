<?php

namespace App\Http\Controllers\Admin\OnlineCourse;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\OnlineCourse\StoreOnlineCourseAssignmentRequest;
use App\Http\Resources\OnlineCourse\OnlineCourseAssignmentResource;
use App\Services\OnlineCourse\OnlineCourseAssignmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class OnlineCourseAssignmentController extends Controller
{
    public function __construct(private readonly OnlineCourseAssignmentService $service) {}

    public function getAll(Request $request): AnonymousResourceCollection
    {
        $filters = $request->only(['course_online_id', 'user_id']);
        $perPage = (int) $request->query('per_page', 15);

        $assignments = $this->service->getAllAssignments($filters, $perPage);

        return OnlineCourseAssignmentResource::collection($assignments)
            ->additional(['cards' => $this->service->getAssignmentCards()]);
    }

    public function create(StoreOnlineCourseAssignmentRequest $request): JsonResponse
    {
        $assignment = $this->service->createAssignment($request->validated());

        return (new OnlineCourseAssignmentResource($assignment->load(['course', 'user'])))
            ->response()
            ->setStatusCode(201);
    }

    public function delete(int $id): JsonResponse
    {
        $this->service->deleteAssignment($id);

        return response()->json(['message' => 'Assignment deleted successfully.']);
    }
}
