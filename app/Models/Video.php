<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Video extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'legacy_id',
        'name',
        'description',
        'file_path',
        'file_size',
        'duration_seconds',
        'thumbnail_path',
        'subtitle_vtt_path',
        'video_category_id',
        'transcode_status',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'file_size'        => 'integer',
            'duration_seconds' => 'integer',
            'deleted_at'       => 'datetime',
        ];
    }

    public function videoCategory(): BelongsTo
    {
        return $this->belongsTo(VideoCategory::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function qualities(): HasMany
    {
        return $this->hasMany(VideoQuality::class);
    }
}
