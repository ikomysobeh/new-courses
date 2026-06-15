<?php

namespace App\Http\Controllers\Api\Admin\Reporting;

use App\Http\Controllers\Controller;
use App\Http\Requests\Reporting\KpiFilterRequest;
use App\Http\Resources\Reporting\KpiOverviewResource;
use App\Services\Reporting\Query\KpiQueryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

class ReportingKpiController extends Controller
{
    public function __construct(protected KpiQueryService $kpi) {}

    public function overview(KpiFilterRequest $request): JsonResponse
    {
        $filters  = $request->validated();
        $cacheKey = 'reporting:kpi:overview:' . md5(serialize($filters));

        $data = Cache::remember($cacheKey, now()->addMinutes(30), function () use ($filters) {
            return $this->kpi->overview($filters);
        });

        return response()->json(new KpiOverviewResource($data));
    }

    public function trends(KpiFilterRequest $request): JsonResponse
    {
        $filters  = $request->validated();
        $cacheKey = 'reporting:kpi:trends:' . md5(serialize($filters));

        $data = Cache::remember($cacheKey, now()->addHour(), function () use ($filters) {
            return $this->kpi->trends($filters);
        });

        return response()->json(['data' => $data]);
    }
}
