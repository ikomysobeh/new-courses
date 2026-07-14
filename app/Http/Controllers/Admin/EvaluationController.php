<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Evaluation\BulkStoreEvaluationRequest;
use App\Http\Requests\Evaluation\StoreEvaluationRequest;
use App\Http\Requests\Evaluation\UpdateEvaluationRequest;
use App\Http\Resources\Evaluation\EvaluationResource;
use App\Services\Evaluation\Submission\EvaluationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class EvaluationController extends Controller
{
    public function __construct(private readonly EvaluationService $service) {}

    public function getAll(Request $request): AnonymousResourceCollection
    {
        return EvaluationResource::collection($this->service->getAllForAdmin($request->query()));
    }

    public function getById(int $id): EvaluationResource
    {
        return new EvaluationResource($this->service->getById($id));
    }

    public function create(StoreEvaluationRequest $request): JsonResponse
    {
        $evaluation = $this->service->createEvaluation($request->validated());

        return (new EvaluationResource($evaluation))
            ->response()
            ->setStatusCode(201);
    }

    public function update(UpdateEvaluationRequest $request, int $id): EvaluationResource
    {
        return new EvaluationResource($this->service->updateEvaluation($id, $request->validated()));
    }

    public function delete(int $id): JsonResponse
    {
        $this->service->deleteEvaluation($id);

        return response()->json(['message' => 'Evaluation deleted successfully.']);
    }

    public function bulkCreate(BulkStoreEvaluationRequest $request): JsonResponse
    {
        $result = $this->service->bulkCreateEvaluations($request->validated('evaluations'));

        return response()->json($result, 200);
    }

    public function users(Request $request): JsonResponse
    {
        $deptId     = (int) $request->query('department_id');
        $courseType = $request->query('course_type', 'regular');

        $users = $this->service->getUsersWithCoursesByDepartment($deptId, $courseType);

        return response()->json(['data' => $users]);
    }

    public function userCourses(Request $request): JsonResponse
    {
        $userId     = (int) $request->query('user_id');
        $courseType = $request->query('course_type', 'regular');
        // dd($courseType);

        $courses = $this->service->getUserCourses($userId, $courseType);

        return response()->json(['data' => $courses]);
    }
}
