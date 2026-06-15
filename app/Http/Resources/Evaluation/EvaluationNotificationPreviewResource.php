<?php

namespace App\Http\Resources\Evaluation;

use Illuminate\Http\Request;
use App\Http\Resources\BaseResource;

class EvaluationNotificationPreviewResource extends BaseResource
{
    public function toArray(Request $request): array
    {
        // $this->resource is the plain array returned by EvaluationNotificationService::previewNotification()
        return $this->resource;
    }
}
