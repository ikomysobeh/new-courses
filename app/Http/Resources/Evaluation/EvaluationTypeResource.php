<?php

namespace App\Http\Resources\Evaluation;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EvaluationTypeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                   => $this->id,
            'evaluation_config_id' => $this->evaluation_config_id,
            'type_name'            => $this->type_name,
            'score_value'          => $this->score_value,
        ];
    }
}
