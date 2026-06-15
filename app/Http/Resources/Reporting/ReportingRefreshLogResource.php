<?php

namespace App\Http\Resources\Reporting;

use Illuminate\Http\Request;
use App\Http\Resources\BaseResource;

class ReportingRefreshLogResource extends BaseResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'               => $this->id,
            'report_table'     => $this->report_table,
            'report_date'      => $this->report_date,
            'refreshed_at'     => $this->refreshed_at,
            'duration_seconds' => (int) $this->duration_seconds,
            'rows_written'     => (int) $this->rows_written,
            'status'           => $this->status,
            'error_message'    => $this->error_message,
            'created_at'       => $this->created_at,
        ];
    }
}
