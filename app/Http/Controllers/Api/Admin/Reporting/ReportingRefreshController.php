<?php

namespace App\Http\Controllers\Api\Admin\Reporting;

use App\Http\Controllers\Controller;
use App\Http\Requests\Reporting\ReportingDateRangeRequest;
use App\Http\Resources\Reporting\ReportingRefreshLogResource;
use App\Jobs\Reporting\RefreshDepartmentCourseDailyJob;
use App\Jobs\Reporting\RefreshUserCourseDailyJob;
use App\Jobs\Reporting\SyncLearningSessionFactJob;
use App\Services\Reporting\ReportingRefreshService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportingRefreshController extends Controller
{
    public function __construct(protected ReportingRefreshService $refresh) {}

    /**
     * POST /admin/reporting/refresh/daily
     * Trigger a refresh for yesterday (or a specific date).
     */
    public function daily(Request $request): JsonResponse
    {
        $request->validate(['date' => ['nullable', 'date']]);
        $date   = $request->input('date') ? Carbon::parse($request->input('date')) : Carbon::yesterday();
        $result = $this->refresh->refreshDaily($date);
        return response()->json($result);
    }

    /**
     * POST /admin/reporting/refresh/range
     * Trigger a refresh for a date range.
     */
    public function range(ReportingDateRangeRequest $request): JsonResponse
    {
        $from   = Carbon::parse($request->validated()['date_from']);
        $to     = Carbon::parse($request->validated()['date_to']);
        $result = $this->refresh->refreshDateRange($from, $to);
        return response()->json($result);
    }

    /**
     * POST /admin/reporting/refresh/full
     * Full rebuild from earliest session.
     */
    public function full(): JsonResponse
    {
        $result = $this->refresh->refreshFull();
        return response()->json($result);
    }

    /**
     * GET /admin/reporting/refresh/log
     * List last N refresh log entries.
     */
    public function log(Request $request): JsonResponse
    {
        $limit = min((int) $request->query('limit', 50), 200);
        $rows  = DB::table('reporting_refresh_log')
            ->orderByDesc('refreshed_at')
            ->limit($limit)
            ->get();

        return response()->json(ReportingRefreshLogResource::collection($rows)->resolve());
    }
}
