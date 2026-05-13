<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\Evaluation\EvaluationResource;
use App\Http\Resources\Evaluation\EvaluationSummaryResource;
use App\Services\Evaluation\History\EvaluationHistoryService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\StreamedResponse;

class EvaluationHistoryController extends Controller
{
    public function __construct(private readonly EvaluationHistoryService $service) {}

    public function getAll(Request $request): AnonymousResourceCollection
    {
        $filters = $request->only(['department_id', 'user_id', 'course_type', 'performance_level', 'start_date', 'end_date']);

        return EvaluationResource::collection($this->service->getHistory($filters));
    }

    public function getById(int $id): EvaluationResource
    {
        return new EvaluationResource($this->service->getById($id));
    }

    public function analytics(Request $request): EvaluationSummaryResource
    {
        $filters = $request->only(['department_id', 'course_type', 'start_date', 'end_date']);

        return new EvaluationSummaryResource($this->service->getAnalytics($filters));
    }

    public function export(Request $request): StreamedResponse
    {
        $filters = $request->only(['department_id', 'user_id', 'course_type', 'performance_level', 'start_date', 'end_date']);

        return $this->service->exportCsv($filters);
    }

    public function exportSummary(Request $request): StreamedResponse
    {
        $filters = $request->only(['start_date', 'end_date']);

        return $this->service->exportSummaryCsv($filters);
    }
}
