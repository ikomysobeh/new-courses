<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\CompleteCourseRequest;
use App\Http\Requests\User\EnrollCourseRequest;
use App\Http\Requests\User\SubmitCourseRatingRequest;
use App\Http\Resources\Course\CourseRegistrationResource;
use App\Http\Resources\Course\CourseResource;
use App\Services\Course\CourseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CourseController extends Controller
{
    public function __construct(private readonly CourseService $courseService) {}

    /** Get all accessible courses for the authenticated user. */
    public function getAll(Request $request): AnonymousResourceCollection
    {
        $courses = $this->courseService->getCoursesForUser($request->user(), $request->only('search'));

        return CourseResource::collection($courses);
    }

    /** Get a single course by ID (access-controlled). */
    public function getById(Request $request, int $id): CourseResource
    {
        $course = $this->courseService->getCourseDetail($id, $request->user());

        return new CourseResource($course);
    }

    /** Enroll the authenticated user in a course availability. */
    public function enroll(EnrollCourseRequest $request, int $courseId): JsonResponse
    {
        $registration = $this->courseService->enrollUserInCourse(
            courseId: $courseId,
            availabilityId: $request->validated('course_availability_id'),
            user: $request->user(),
        );

        return (new CourseRegistrationResource($registration))
            ->response()
            ->setStatusCode(201);
    }

    /** Mark the authenticated user's course as completed. */
    public function complete(CompleteCourseRequest $request, int $courseId): CourseRegistrationResource
    {
        $registration = $this->courseService->completeCourse($courseId, $request->user());

        return new CourseRegistrationResource($registration);
    }

    /** Submit a rating for a completed course. */
    public function submitRating(SubmitCourseRatingRequest $request, int $courseId): CourseRegistrationResource
    {
        $validated    = $request->validated();
        $registration = $this->courseService->submitRating(
            courseId: $courseId,
            rating: $validated['rating'],
            feedback: $validated['feedback'] ?? null,
            user: $request->user(),
        );

        return new CourseRegistrationResource($registration);
    }

    /** Get the authenticated user's course enrollments. */
    public function myEnrollments(Request $request): AnonymousResourceCollection
    {
        $enrollments = $this->courseService->getUserEnrollments($request->user());

        return CourseRegistrationResource::collection($enrollments);
    }
}
