<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use App\Http\Resources\BaseResource;

/** @mixin \App\Models\User */
class UserListResource extends BaseResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'    => $this->id,
            'name'  => $this->name,
            'email' => $this->email,
            'role'  => $this->role,
            'department' => $this->whenLoaded('department', fn () => [
                'id'   => $this->department->id,
                'name' => $this->department->name,
            ]),
            'manager' => $this->whenLoaded('manager', fn () => $this->manager ? [
                'id'   => $this->manager->id,
                'name' => $this->manager->name,
            ] : null),
            'tier' => $this->whenLoaded('userLevelTier', fn () => $this->userLevelTier ? [
                'id'         => $this->userLevelTier->id,
                'tier_name'  => $this->userLevelTier->tier_name,
                'level_name' => optional($this->userLevelTier->userLevel)->name,
            ] : null),
            'created_at' => $this->created_at,
        ];
    }
}
