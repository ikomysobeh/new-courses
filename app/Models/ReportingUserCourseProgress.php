<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReportingUserCourseProgress extends Model
{
    protected $table = 'reporting_user_course_progress';

    protected $fillable = [
        'user_id',
        'course_type',
        'course_id',
        'department_id',
        'user_name',
        'department_name',
        'course_name',
        'course_beginning_date',
        'status',
        'completion_status',
        'is_completed',
        'days_overdue',
        'progress_percentage',
        'started_at',
        'completed_at',
        'deadline',
        'attention_score',
        'quiz_score',
        'completion_rate',
        'learning_score',
        'score_band',
        'compliance_status',
        'snapshot_date',
    ];

    protected $casts = [
        'course_beginning_date' => 'date',
        'is_completed'          => 'boolean',
        'days_overdue'          => 'integer',
        'progress_percentage'   => 'decimal:2',
        'started_at'            => 'datetime',
        'completed_at'          => 'datetime',
        'deadline'              => 'datetime',
        'attention_score'       => 'decimal:2',
        'quiz_score'            => 'decimal:2',
        'completion_rate'       => 'integer',
        'learning_score'        => 'decimal:2',
        'snapshot_date'         => 'date',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }
}
