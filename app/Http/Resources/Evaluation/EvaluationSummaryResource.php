<?php

namespace App\Http\Resources\Evaluation;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EvaluationSummaryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        // $this->resource is the plain array returned by EvaluationHistoryService::getAnalytics()
        return $this->resource;
    }
}
