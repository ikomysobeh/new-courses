<?php

namespace App\Http\Controllers\Api\Admin\Reporting;

use App\Http\Controllers\Controller;
use App\Http\Requests\Reporting\AttendanceFilterRequest;
use App\Http\Requests\Reporting\CourseCompletionFilterRequest;
use App\Http\Requests\Reporting\CourseRegistrationFilterRequest;
use App\Http\Requests\Reporting\EvaluationDepartmentFilterRequest;
use App\Http\Requests\Reporting\QuizAttemptFilterRequest;
use App\Http\Requests\Reporting\QuizDetailedExportRequest;
use App\Http\Requests\Reporting\UserCourseProgressFilterRequest;
use App\Http\Requests\Reporting\UserPerformanceFilterRequest;
use App\Services\Reporting\Export\ExtendedReportCsvExportService;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * CSV exports for the live-course, quiz, user-performance, compliance and
 * evaluation reports. Filters mirror the corresponding JSON endpoints.
 */
class ReportingExtraExportController extends Controller
{
    public function __construct(protected ExtendedReportCsvExportService $csv) {}

    public function courseRegistrations(CourseRegistrationFilterRequest $request): StreamedResponse
    {
        return $this->csv->exportCourseRegistrations($request->validated());
    }

    public function attendance(AttendanceFilterRequest $request): StreamedResponse
    {
        return $this->csv->exportAttendance($request->validated());
    }

    public function courseCompletion(CourseCompletionFilterRequest $request): StreamedResponse
    {
        return $this->csv->exportCourseCompletion($request->validated());
    }

    public function quizAttempts(QuizAttemptFilterRequest $request): StreamedResponse
    {
        return $this->csv->exportQuizAttempts($request->validated());
    }

    public function quizDetailed(QuizDetailedExportRequest $request): StreamedResponse
    {
        return $this->csv->exportQuizDetailed($request->validated());
    }

    public function userPerformance(UserPerformanceFilterRequest $request): StreamedResponse
    {
        return $this->csv->exportUserPerformance($request->validated());
    }

    public function userCourseProgress(UserCourseProgressFilterRequest $request): StreamedResponse
    {
        return $this->csv->exportUserCourseProgress($request->validated());
    }

    public function evaluationDepartment(EvaluationDepartmentFilterRequest $request): StreamedResponse
    {
        return $this->csv->exportEvaluationDepartment($request->validated());
    }
}
