<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreCourseAssignmentRequest;
use App\Http\Resources\Course\CourseAssignmentResource;
use App\Services\Course\CourseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CourseAssignmentController extends Controller
{
    public function __construct(private readonly CourseService $courseService) {}

    /** Get all course assignments (paginated). */
    public function getAll(Request $request): AnonymousResourceCollection
    {
        $assignments = $this->courseService->getAllAssignmentsForAdmin($request->only('course_id', 'user_id'));

        return CourseAssignmentResource::collection($assignments)
            ->additional([
                'cards' => $this->courseService->getAdminCourseAssignmentCards(),
            ]);
    }

    /** Assign a course to a user. */
    public function create(StoreCourseAssignmentRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $assignment = $this->courseService->assignCourseToUser(
            courseId: $validated['course_id'],
            userId: $validated['user_id'],
            availabilityId: $validated['course_availability_id'] ?? null,
            admin: $request->user(),
        );

        return (new CourseAssignmentResource($assignment))
            ->response()
            ->setStatusCode(201);
    }

    /** Delete a course assignment. */
    public function delete(int $id): JsonResponse
    {
        $this->courseService->removeAssignment($id);

        return response()->json(['message' => 'Assignment removed successfully.']);
    }
}
