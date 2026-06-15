<?php

namespace App\Http\Resources\Evaluation;

use Illuminate\Http\Request;
use App\Http\Resources\BaseResource;

class EvaluationConfigResource extends BaseResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'         => $this->id,
            'name'       => $this->name,
            'max_score'  => $this->max_score,
            'applies_to' => $this->applies_to,
            'types'      => EvaluationTypeResource::collection($this->whenLoaded('types')),
        ];
    }
}
