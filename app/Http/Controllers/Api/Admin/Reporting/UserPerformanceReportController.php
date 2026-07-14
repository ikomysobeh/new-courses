<?php

namespace App\Http\Controllers\Api\Admin\Reporting;

use App\Http\Controllers\Controller;
use App\Http\Requests\Reporting\UserCourseProgressFilterRequest;
use App\Http\Requests\Reporting\UserPerformanceFilterRequest;
use App\Http\Resources\Reporting\UserCourseProgressResource;
use App\Http\Resources\Reporting\UserPerformanceResource;
use App\Services\Reporting\Query\UserCourseProgressQueryService;
use App\Services\Reporting\Query\UserPerformanceQueryService;
use Illuminate\Http\JsonResponse;

/**
 * Per-user performance report and compliance-oriented course progress report.
 */
class UserPerformanceReportController extends Controller
{
    public function __construct(
        protected UserPerformanceQueryService    $performance,
        protected UserCourseProgressQueryService $progress,
    ) {}

    public function performance(UserPerformanceFilterRequest $request): JsonResponse
    {
        $perPage = (int) ($request->validated()['per_page'] ?? 25);
        $data    = $this->performance->query($request->validated(), $perPage);

        return response()->json(UserPerformanceResource::collection($data)->response()->getData(true));
    }

    public function courseProgress(UserCourseProgressFilterRequest $request): JsonResponse
    {
        $perPage = (int) ($request->validated()['per_page'] ?? 25);
        $data    = $this->progress->query($request->validated(), $perPage);

        return response()->json(UserCourseProgressResource::collection($data)->response()->getData(true));
    }

    public function performanceShow(UserPerformanceFilterRequest $request, int $id): JsonResponse
    {
        $row = $this->performance->find($id, $request->validated());

        abort_if($row === null, 404, 'User performance record not found.');

        return response()->json(['data' => new UserPerformanceResource($row)]);
    }
}
