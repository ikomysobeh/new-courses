<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CourseModule extends Model
{
    use HasFactory;

    protected $fillable = [
        'course_online_id',
        'name',
        'description',
        'order_number',
        'estimated_duration',
        'has_quiz',
        'quiz_required',
    ];

    protected function casts(): array
    {
        return [
            'has_quiz'     => 'boolean',
            'quiz_required' => 'boolean',
        ];
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(CourseOnline::class, 'course_online_id');
    }

    public function contents(): HasMany
    {
        return $this->hasMany(ModuleContent::class, 'module_id')->orderBy('order_number');
    }
}
