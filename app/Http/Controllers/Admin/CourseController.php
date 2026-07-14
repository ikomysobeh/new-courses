<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreCourseRequest;
use App\Http\Requests\Admin\UpdateCourseRequest;
use App\Http\Resources\Course\CourseResource;
use App\Services\Course\CourseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CourseController extends Controller
{
    public function __construct(private readonly CourseService $courseService) {}

    /** Get all courses (admin view, paginated). */
    public function getAll(Request $request): AnonymousResourceCollection
    {
        $courses = $this->courseService->getAllCoursesForAdmin($request->query());

        return CourseResource::collection($courses)
            ->additional([
                'cards' => $this->courseService->getAdminCourseCards(),
            ]);
    }

    /** Create a new course with one or more availabilities. */
    public function create(StoreCourseRequest $request): JsonResponse
    {
        $course = $this->courseService->createCourse($request->validated(), $request->user());

        return (new CourseResource($course))
            ->response()
            ->setStatusCode(201);
    }

    /** Get a single course by ID. */
    public function getById(int $id): CourseResource
    {
        $course = $this->courseService->getCourseByIdForAdmin($id);

        return new CourseResource($course);
    }

    /** Update a course and its availabilities. */
    public function update(UpdateCourseRequest $request, int $id): CourseResource
    {
        $course = $this->courseService->updateCourse($id, $request->validated(), $request->user());

        return new CourseResource($course);
    }

    /** Soft delete a course. */
    public function delete(int $id): JsonResponse
    {
        $this->courseService->deleteCourse($id);

        return response()->json(['message' => 'Course deleted successfully.']);
    }
}
