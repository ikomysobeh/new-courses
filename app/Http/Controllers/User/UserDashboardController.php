<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Services\Dashboard\UserDashboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserDashboardController extends Controller
{
    public function __construct(private readonly UserDashboardService $service) {}

    public function __invoke(Request $request): JsonResponse
    {
        return response()->json([
            'data' => $this->service->build($request->user()),
        ]);
    }
}
