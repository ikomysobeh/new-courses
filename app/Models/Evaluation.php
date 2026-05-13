<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Evaluation extends Model
{
    protected $fillable = [
        'user_id',
        'department_id',
        'course_type',
        'course_id',
        'course_online_id',
        'total_score',
        'performance_level',
        'performance_points_min',
        'performance_points_max',
    ];

    protected function casts(): array
    {
        return [
            'total_score'            => 'integer',
            'performance_level'      => 'integer',
            'performance_points_min' => 'integer',
            'performance_points_max' => 'integer',
        ];
    }

    // ---- Relationships ----

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function courseOnline(): BelongsTo
    {
        return $this->belongsTo(CourseOnline::class, 'course_online_id');
    }

    public function histories(): HasMany
    {
        return $this->hasMany(EvaluationHistory::class);
    }

    // ---- Scopes ----

    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeForDepartment($query, int $departmentId)
    {
        return $query->where('department_id', $departmentId);
    }

    public function scopeByLevel($query, int $level)
    {
        return $query->where('performance_level', $level);
    }

    public function scopeForCourseType($query, string $type)
    {
        return $query->where('course_type', $type);
    }
}
