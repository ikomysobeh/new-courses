<?php

namespace App\Http\Controllers\Api\Admin\Reporting;

use App\Http\Controllers\Controller;
use App\Http\Requests\Reporting\DepartmentReportFilterRequest;
use App\Http\Requests\Reporting\SessionFactFilterRequest;
use App\Http\Requests\Reporting\UserCourseReportFilterRequest;
use App\Http\Resources\Reporting\DepartmentCourseDailyResource;
use App\Http\Resources\Reporting\LearningSessionFactResource;
use App\Http\Resources\Reporting\UserCourseDailyResource;
use App\Services\Reporting\Query\DepartmentDailyQueryService;
use App\Services\Reporting\Query\SessionFactQueryService;
use App\Services\Reporting\Query\UserCourseDailyQueryService;
use Illuminate\Http\JsonResponse;

class ReportingDatasetController extends Controller
{
    public function __construct(
        protected UserCourseDailyQueryService  $userQuery,
        protected DepartmentDailyQueryService  $deptQuery,
        protected SessionFactQueryService      $sessionQuery,
    ) {}

    public function userCourseDaily(UserCourseReportFilterRequest $request): JsonResponse
    {
        $perPage = (int) ($request->validated()['per_page'] ?? 25);
        $data    = $this->userQuery->query($request->validated(), $perPage);
        return response()->json(UserCourseDailyResource::collection($data)->response()->getData(true));
    }

    public function departmentCourseDaily(DepartmentReportFilterRequest $request): JsonResponse
    {
        $perPage = (int) ($request->validated()['per_page'] ?? 25);
        $data    = $this->deptQuery->query($request->validated(), $perPage);
        return response()->json(DepartmentCourseDailyResource::collection($data)->response()->getData(true));
    }

    public function sessionFact(SessionFactFilterRequest $request): JsonResponse
    {
        $perPage = (int) ($request->validated()['per_page'] ?? 25);
        $data    = $this->sessionQuery->query($request->validated(), $perPage);
        return response()->json(LearningSessionFactResource::collection($data)->response()->getData(true));
    }

    public function sessionFactShow(int $id): JsonResponse
    {
        $row = $this->sessionQuery->find($id);

        abort_if($row === null, 404, 'Session fact not found.');

        return response()->json(['data' => new LearningSessionFactResource($row)]);
    }
}
