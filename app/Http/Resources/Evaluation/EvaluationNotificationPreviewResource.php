<?php

namespace App\Http\Resources\Evaluation;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EvaluationNotificationPreviewResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        // $this->resource is the plain array returned by EvaluationNotificationService::previewNotification()
        return $this->resource;
    }
}
