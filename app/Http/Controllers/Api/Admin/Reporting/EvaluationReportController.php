<?php

namespace App\Http\Controllers\Api\Admin\Reporting;

use App\Http\Controllers\Controller;
use App\Http\Requests\Reporting\EvaluationDepartmentFilterRequest;
use App\Services\Reporting\Query\EvaluationDepartmentQueryService;
use Illuminate\Http\JsonResponse;

/**
 * Department performance based on evaluation scores
 * (top / bottom performers per department).
 */
class EvaluationReportController extends Controller
{
    public function __construct(protected EvaluationDepartmentQueryService $evaluations) {}

    public function departmentPerformance(EvaluationDepartmentFilterRequest $request): JsonResponse
    {
        $data = $this->evaluations->generate($request->validated());

        return response()->json(['data' => $data]);
    }
}
