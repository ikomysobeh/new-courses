<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use App\Http\Resources\BaseResource;
use App\Http\Resources\Admin\UserResource;

/** @mixin \App\Models\Department */
class DepartmentResource extends BaseResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'name'        => $this->name,
            'slug'        => $this->slug,
            'parent_id'   => $this->parent_id,
            'parent'      => $this->whenLoaded('parent', fn () => $this->parent
                ? ['id' => $this->parent->id, 'name' => $this->parent->name]
                : null),
            'users_count' => $this->whenCounted('users', fn () => $this->users_count),
            'users'       => UserResource::collection($this->whenLoaded('users')),
            'sort_order'  => $this->sort_order,
            'created_at'  => $this->created_at,
            'updated_at'  => $this->updated_at,
        ];
    }
}
