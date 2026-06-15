<?php

namespace App\Http\Resources\Reporting;

use Illuminate\Http\Request;
use App\Http\Resources\BaseResource;

class KpiOverviewResource extends BaseResource
{
    public function toArray(Request $request): array
    {
        return [
            'period'               => $this->resource['period']               ?? null,
            'total_sessions'       => (int)   ($this->resource['total_sessions']       ?? 0),
            'total_active_seconds' => (int)   ($this->resource['total_active_seconds'] ?? 0),
            'avg_completion_pct'   => (float) ($this->resource['avg_completion_pct']   ?? 0),
            'avg_attention_score'  => (float) ($this->resource['avg_attention_score']  ?? 0),
            'suspicious_sessions'  => (int)   ($this->resource['suspicious_sessions']  ?? 0),
            'enrolled_users'       => (int)   ($this->resource['enrolled_users']       ?? 0),
            'completed_users'      => (int)   ($this->resource['completed_users']      ?? 0),
            'completion_rate'      => (float) ($this->resource['completion_rate']      ?? 0),
        ];
    }
}
