<?php

namespace App\Http\Resources\Evaluation;

use Illuminate\Http\Request;
use App\Http\Resources\BaseResource;

class EvaluationHistoryResource extends BaseResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'            => $this->id,
            'evaluation_id' => $this->evaluation_id,
            'config_name'   => $this->config_name,
            'type_name'     => $this->type_name,
            'score_given'   => $this->score_given,
            'max_score'     => $this->max_score,
        ];
    }
}
