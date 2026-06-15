<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use App\Http\Resources\BaseResource;

/** @mixin \App\Models\User */
class UserResource extends BaseResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'    => $this->id,
            'name'  => $this->name,
            'email' => $this->email,
            'role'  => $this->role,
            'department' => $this->whenLoaded('department', fn () => $this->department ? [
                'id'   => $this->department->id,
                'name' => $this->department->name,
                'slug' => $this->department->slug,
            ] : null),
            'manager' => $this->whenLoaded('manager', fn () => $this->manager ? [
                'id'   => $this->manager->id,
                'name' => $this->manager->name,
            ] : null),
            'tier' => $this->whenLoaded('userLevelTier', fn () => $this->userLevelTier ? [
                'id'        => $this->userLevelTier->id,
                'tier_name' => $this->userLevelTier->tier_name,
                'level'     => $this->userLevelTier->relationLoaded('userLevel') && $this->userLevelTier->userLevel ? [
                    'id'   => $this->userLevelTier->userLevel->id,
                    'name' => $this->userLevelTier->userLevel->name,
                ] : null,
            ] : null),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
