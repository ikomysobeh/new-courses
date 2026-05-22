<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\UserLevel;
use Illuminate\Http\JsonResponse;

class UserLevelController extends Controller
{
    public function withTiers(): JsonResponse
    {
        $levels = UserLevel::with(['tiers' => fn ($q) => $q->orderBy('tier_order')])
            ->orderBy('hierarchy_level')
            ->get()
            ->map(fn (UserLevel $level) => [
                'id'              => $level->id,
                'code'            => $level->code,
                'name'            => $level->name,
                'hierarchy_level' => $level->hierarchy_level,
                'tiers'           => $level->tiers->map(fn ($tier) => [
                    'id'         => $tier->id,
                    'tier_name'  => $tier->tier_name,
                    'tier_order' => $tier->tier_order,
                ])->values(),
            ]);

        return response()->json(['data' => $levels]);
    }
}
