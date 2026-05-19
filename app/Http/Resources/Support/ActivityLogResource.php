<?php

namespace App\Http\Resources\Support;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ActivityLogResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'action'      => $this->action,
            'description' => $this->description,
            'model_type'  => $this->model_type,
            'model_id'    => $this->model_id,
            'properties'  => $this->properties,
            'user'        => $this->when($this->relationLoaded('user'), fn () => $this->user ? [
                'id'   => $this->user->id,
                'name' => $this->user->name,
            ] : null),
            'created_at'  => $this->created_at?->toISOString(),
        ];
    }
}
