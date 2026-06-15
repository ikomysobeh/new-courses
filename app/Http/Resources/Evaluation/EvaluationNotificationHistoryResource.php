<?php

namespace App\Http\Resources\Evaluation;

use Illuminate\Http\Request;
use App\Http\Resources\BaseResource;

class EvaluationNotificationHistoryResource extends BaseResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'           => $this->id,
            'subject'      => $this->subject,
            'message'      => $this->message,
            'status'       => $this->status,
            'sent_at'   => $this->sent_at?->toISOString(),
            'managers'  => $this->resolved_managers ?? [],
            'employees' => $this->resolved_employees ?? [],
        ];
    }
}
