<?php

namespace App\Http\Controllers\Api\Admin\Reporting;

use App\Http\Controllers\Controller;
use App\Http\Requests\Reporting\QuizAttemptFilterRequest;
use App\Http\Resources\Reporting\QuizAttemptResource;
use App\Services\Reporting\Query\QuizAttemptQueryService;
use Illuminate\Http\JsonResponse;

/**
 * Quiz attempts report. (Question-level detail is available via CSV export.)
 */
class QuizReportController extends Controller
{
    public function __construct(protected QuizAttemptQueryService $attempts) {}

    public function attempts(QuizAttemptFilterRequest $request): JsonResponse
    {
        $perPage = (int) ($request->validated()['per_page'] ?? 25);
        $data    = $this->attempts->query($request->validated(), $perPage);

        return response()->json(QuizAttemptResource::collection($data)->response()->getData(true));
    }
}
