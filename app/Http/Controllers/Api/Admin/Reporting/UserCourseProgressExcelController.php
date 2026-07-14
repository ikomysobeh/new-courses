<?php

namespace App\Http\Controllers\Api\Admin\Reporting;

use App\Http\Controllers\Controller;
use App\Http\Requests\Reporting\UserCourseProgressFilterRequest;
use App\Services\Reporting\Export\UserCourseProgressExcelService;
use App\Services\Reporting\Progress\UserCourseProgressReportService;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Styled 2-sheet Excel export of the User Course Progress report (KPI).
 * Matches the legacy nvt-courses file: Completed / Non-Completed sheets.
 */
class UserCourseProgressExcelController extends Controller
{
    public function __construct(
        protected UserCourseProgressReportService $report,
        protected UserCourseProgressExcelService $excel,
    ) {}

    public function export(UserCourseProgressFilterRequest $request): BinaryFileResponse
    {
        $rows = $this->report->buildRows($request->validated());

        return $this->excel->export($rows);
    }
}
