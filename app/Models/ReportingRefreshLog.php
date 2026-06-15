<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReportingRefreshLog extends Model
{
    protected $table = 'reporting_refresh_log';

    // audit table — no updated_at
    public $timestamps = false;

    protected $fillable = [
        'report_table',
        'report_date',
        'refreshed_at',
        'duration_seconds',
        'rows_written',
        'status',
        'error_message',
        'created_at',
    ];

    protected $casts = [
        'report_date'      => 'date',
        'refreshed_at'     => 'datetime',
        'duration_seconds' => 'integer',
        'rows_written'     => 'integer',
        'created_at'       => 'datetime',
    ];
}
