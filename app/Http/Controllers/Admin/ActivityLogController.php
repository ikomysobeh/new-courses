<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\Support\ActivityLogResource;
use App\Services\ActivityService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ActivityLogController extends Controller
{
    public function getAll(Request $request): AnonymousResourceCollection
    {
        $query = \App\Models\ActivityLog::with('user')->latest();

        if ($request->filled('user_id')) {
            $query->forUser((int) $request->query('user_id'));
        }

        if ($request->filled('action')) {
            $query->byAction($request->query('action'));
        }

        if ($request->filled('model_type')) {
            $query->where('model_type', $request->query('model_type'));
        }

        if ($request->filled('date_from')) {
            $query->where('created_at', '>=', $request->query('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->where('created_at', '<=', $request->query('date_to'));
        }

        return ActivityLogResource::collection($query->paginate(20));
    }

    public function user(int $userId): AnonymousResourceCollection
    {
        return ActivityLogResource::collection(
            ActivityService::getUserActivities($userId, 50)
        );
    }
}
