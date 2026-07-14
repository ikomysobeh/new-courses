<?php

namespace App\Http\Controllers\Admin\OnlineCourse;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\OnlineCourse\ReorderModulesRequest;
use App\Http\Requests\Admin\OnlineCourse\StoreOnlineCourseRequest;
use App\Http\Requests\Admin\OnlineCourse\UpdateOnlineCourseRequest;
use App\Http\Resources\OnlineCourse\OnlineCourseDetailResource;
use App\Http\Resources\OnlineCourse\OnlineCourseResource;
use App\Services\OnlineCourse\OnlineCourseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class OnlineCourseController extends Controller
{
    public function __construct(private readonly OnlineCourseService $service) {}

    public function getAll(Request $request): AnonymousResourceCollection
    {
        $courses = $this->service->getAllForAdmin($request->query());

        return OnlineCourseResource::collection($courses)
            ->additional(['cards' => $this->service->getAdminCourseCards()]);
    }

    public function create(StoreOnlineCourseRequest $request): JsonResponse
    {
        $course = $this->service->createCourse($request->validated(), $request->user());

        return (new OnlineCourseDetailResource($course))
            ->response()
            ->setStatusCode(201);
    }

    public function getById(int $id): OnlineCourseDetailResource
    {
        $course = $this->service->getCourseById($id);

        return new OnlineCourseDetailResource($course);
    }

    public function update(UpdateOnlineCourseRequest $request, int $id): OnlineCourseDetailResource
    {
        $course = $this->service->updateCourse($id, $request->validated(), $request->user());

        return new OnlineCourseDetailResource($course);
    }

    public function delete(int $id): Response
    {
        $this->service->deleteCourse($id);

        return response()->noContent();
    }

    public function reorderModules(ReorderModulesRequest $request): JsonResponse
    {
        $this->service->reorderModules($request->validated());

        return response()->json(['message' => 'Modules reordered successfully.']);
    }

    public function enrollments(Request $request, int $id): JsonResponse
    {
        $filters = $request->only(['search', 'status', 'department_id']);
        $perPage = (int) $request->query('per_page', 20);

        $enrollments = $this->service->getCourseEnrollments($id, $filters, $perPage);
        $cards       = $this->service->getCourseEnrollmentCards($id);

        return response()->json([
            'data'  => $enrollments->items(),
            'meta'  => [
                'current_page' => $enrollments->currentPage(),
                'last_page'    => $enrollments->lastPage(),
                'total'        => $enrollments->total(),
                'per_page'     => $enrollments->perPage(),
            ],
            'cards' => $cards,
        ]);
    }
}
