<?php

namespace App\Http\Controllers\Api\Admin\Reporting;

use App\Http\Controllers\Controller;
use App\Http\Requests\Reporting\AttendanceFilterRequest;
use App\Http\Requests\Reporting\CourseCompletionFilterRequest;
use App\Http\Requests\Reporting\CourseRegistrationFilterRequest;
use App\Http\Resources\Reporting\AttendanceResource;
use App\Http\Resources\Reporting\CourseCompletionResource;
use App\Http\Resources\Reporting\CourseRegistrationResource;
use App\Services\Reporting\Query\AttendanceQueryService;
use App\Services\Reporting\Query\CourseCompletionQueryService;
use App\Services\Reporting\Query\CourseRegistrationQueryService;
use Illuminate\Http\JsonResponse;

/**
 * Live / traditional course reports: registrations, attendance, completion.
 */
class LiveCourseReportController extends Controller
{
    public function __construct(
        protected CourseRegistrationQueryService $registrations,
        protected AttendanceQueryService         $attendance,
        protected CourseCompletionQueryService   $completions,
    ) {}

    public function courseRegistrations(CourseRegistrationFilterRequest $request): JsonResponse
    {
        $perPage = (int) ($request->validated()['per_page'] ?? 25);
        $data    = $this->registrations->query($request->validated(), $perPage);

        return response()->json(CourseRegistrationResource::collection($data)->response()->getData(true));
    }

    public function attendance(AttendanceFilterRequest $request): JsonResponse
    {
        $perPage = (int) ($request->validated()['per_page'] ?? 25);
        $data    = $this->attendance->query($request->validated(), $perPage);

        return response()->json(AttendanceResource::collection($data)->response()->getData(true));
    }

    public function courseCompletion(CourseCompletionFilterRequest $request): JsonResponse
    {
        $perPage = (int) ($request->validated()['per_page'] ?? 25);
        $data    = $this->completions->query($request->validated(), $perPage);

        return response()->json(CourseCompletionResource::collection($data)->response()->getData(true));
    }
}
