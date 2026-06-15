<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReportingDepartmentCourseDaily extends Model
{
    protected $table = 'reporting_department_course_daily';

    protected $fillable = [
        'department_id',
        'course_online_id',
        'report_date',
        'enrolled_users',
        'active_users',
        'completed_users',
        'avg_progress_percentage',
        'total_active_seconds',
    ];

    protected $casts = [
        'report_date'             => 'date',
        'enrolled_users'          => 'integer',
        'active_users'            => 'integer',
        'completed_users'         => 'integer',
        'avg_progress_percentage' => 'decimal:2',
        'total_active_seconds'    => 'integer',
    ];

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function courseOnline(): BelongsTo
    {
        return $this->belongsTo(CourseOnline::class, 'course_online_id');
    }
}
