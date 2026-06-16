<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Dashboard\AdminDashboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminDashboardController extends Controller
{
    public function __construct(private readonly AdminDashboardService $service) {}

    public function __invoke(Request $request): JsonResponse
    {
        return response()->json([
            'data' => $this->service->build($request->user()),
        ]);
    }
}
