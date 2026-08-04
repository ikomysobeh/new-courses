<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class CourseOnline extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'legacy_id',
        'name',
        'description',
        'image_path',
        'level',
        'estimated_duration',
        'status',
        'is_active',
        'deadline',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'is_active'  => 'boolean',
            'deadline'   => 'datetime',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function modules(): HasMany
    {
        return $this->hasMany(CourseModule::class, 'course_online_id')->orderBy('order_number');
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(CourseOnlineAssignment::class, 'course_online_id');
    }

    public function analytics(): HasOne
    {
        return $this->hasOne(CourseAnalytics::class, 'course_online_id');
    }

    public function learningProgress(): HasMany
    {
        return $this->hasMany(UserCourseProgress::class, 'course_online_id');
    }
}
