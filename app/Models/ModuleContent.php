<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ModuleContent extends Model
{
    use HasFactory;

    protected $fillable = [
        'legacy_id',
        'module_id',
        'content_type',
        'title',
        'description',
        'order_number',
        'video_id',
        'duration',
        'thumbnail_path',
        'is_required',
        'is_active',
        'attachment_path',
        'attachment_name',
        'attachment_extension',
    ];

    protected function casts(): array
    {
        return [
            'is_required' => 'boolean',
            'is_active'   => 'boolean',
        ];
    }

    public function module(): BelongsTo
    {
        return $this->belongsTo(CourseModule::class, 'module_id');
    }

    public function video(): BelongsTo
    {
        return $this->belongsTo(Video::class, 'video_id');
    }

    public function pdf(): HasOne
    {
        return $this->hasOne(ModuleContentPdf::class, 'module_content_id');
    }

    public function userProgress(): HasMany
    {
        return $this->hasMany(UserContentProgress::class, 'content_id');
    }
}
