<?php

namespace App\Http\Resources\Evaluation;

use Illuminate\Http\Request;
use App\Http\Resources\BaseResource;

class EvaluationSummaryResource extends BaseResource
{
    public function toArray(Request $request): array
    {
        // $this->resource is the plain array returned by EvaluationHistoryService::getAnalytics()
        return $this->resource;
    }
}
