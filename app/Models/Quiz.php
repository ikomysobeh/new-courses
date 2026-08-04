<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Quiz extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'legacy_id',
        'course_id',
        'course_online_id',
        'module_id',
        'title',
        'description',
        'required_to_proceed',
        'max_attempts',
        'retry_delay_hours',
        'show_correct_answers',
        'deadline',
        'time_limit_minutes',
        'status',
        'total_points',
        'pass_threshold',
    ];

    protected function casts(): array
    {
        return [
            'required_to_proceed' => 'boolean',
            'max_attempts' => 'integer',
            'retry_delay_hours' => 'integer',
            'deadline' => 'datetime',
            'time_limit_minutes' => 'integer',
            'total_points' => 'integer',
            'pass_threshold' => 'float',
            'deleted_at' => 'datetime',
        ];
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function courseOnline(): BelongsTo
    {
        return $this->belongsTo(CourseOnline::class, 'course_online_id');
    }

    public function module(): BelongsTo
    {
        return $this->belongsTo(CourseModule::class, 'module_id');
    }

    public function questions(): HasMany
    {
        return $this->hasMany(QuizQuestion::class)->orderBy('order');
    }

    public function attempts(): HasMany
    {
        return $this->hasMany(QuizAttempt::class);
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(QuizAssignment::class);
    }
}
