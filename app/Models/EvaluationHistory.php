<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EvaluationHistory extends Model
{
    protected $fillable = [
        'legacy_id',
        'evaluation_id',
        'course_online_id',
        'config_name',
        'type_name',
        'score_given',
        'max_score',
    ];

    protected function casts(): array
    {
        return [
            'score_given' => 'integer',
            'max_score'   => 'integer',
        ];
    }

    // ---- Relationships ----

    public function evaluation(): BelongsTo
    {
        return $this->belongsTo(Evaluation::class);
    }

    public function courseOnline(): BelongsTo
    {
        return $this->belongsTo(CourseOnline::class, 'course_online_id');
    }

    // ---- Scopes ----

    public function scopeForCourseType($query, string $type)
    {
        if ($type === 'online') {
            return $query->whereNotNull('course_online_id');
        }
        return $query->whereNull('course_online_id');
    }
}
