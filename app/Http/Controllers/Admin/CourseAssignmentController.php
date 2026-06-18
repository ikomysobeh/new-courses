<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreCourseAssignmentRequest;
use App\Http\Resources\Course\CourseAssignmentResource;
use App\Mail\CourseAssignedUserMail;
use App\Models\CourseAssignment;
use App\Services\Course\CourseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Mail;

class CourseAssignmentController extends Controller
{
    public function __construct(private readonly CourseService $courseService) {}

    /** Get all course assignments (paginated). */
    public function getAll(Request $request): AnonymousResourceCollection
    {
        $assignments = $this->courseService->getAllAssignmentsForAdmin($request->only('course_id', 'user_id', 'search', 'per_page'));

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

    /** List assignments where the user's login link has expired or was never sent. */
    public function expiredLinks(): AnonymousResourceCollection
    {
        $assignments = $this->courseService->getAssignmentsWithExpiredLinks();

        return CourseAssignmentResource::collection($assignments);
    }

    /** Resend the magic login link for a course assignment. */
    public function resendLink(int $id): JsonResponse
    {
        $assignment = CourseAssignment::with(['user', 'course', 'assignedBy'])->findOrFail($id);
        $user       = $assignment->user;

        if (! $user->email) {
            return response()->json(['message' => 'User does not have an email address.'], 422);
        }

        $loginLink = $user->generateCourseLoginLink((int) $assignment->course_id);

        Mail::to($user->email)->queue(
            new CourseAssignedUserMail(
                course:       $assignment->course,
                assignedUser: $user,
                assignedBy:   $assignment->assignedBy,
                loginLink:    $loginLink,
            )
        );

        return response()->json(['message' => 'Login link resent successfully.']);
    }
}