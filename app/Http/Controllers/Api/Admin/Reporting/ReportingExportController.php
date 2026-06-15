<?php

namespace App\Http\Controllers\Api\Admin\Reporting;

use App\Http\Controllers\Controller;
use App\Http\Requests\Reporting\ReportExportRequest;
use App\Services\Reporting\Export\ReportingCsvExportService;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportingExportController extends Controller
{
    public function __construct(protected ReportingCsvExportService $csv) {}

    public function userCourseDaily(ReportExportRequest $request): StreamedResponse
    {
        return $this->csv->exportUserCourseDaily($request->validated());
    }

    public function departmentCourseDaily(ReportExportRequest $request): StreamedResponse
    {
        return $this->csv->exportDepartmentCourseDaily($request->validated());
    }

    public function sessionFact(ReportExportRequest $request): StreamedResponse
    {
        return $this->csv->exportSessionFact($request->validated());
    }

    public function kpiOverview(ReportExportRequest $request): StreamedResponse
    {
        return $this->csv->exportKpiOverview($request->validated());
    }
}
