<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

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

    /**
     * A per-upload-unique identifier for correlating with the external
     * transcoding service. The DB's numeric id gets reused after resets/
     * reseeding, which made the VPS (which dedupes jobs by this value)
     * match a brand new upload to a stale job for an old, unrelated file.
     * The uuid embedded in file_path ("videos/{uuid}_name.mp4") is minted
     * fresh per upload in VideoChunkUploadService and never reused, so it's
     * safe to use as the external-facing video identifier instead.
     */
    public function transcodeToken(): string
    {
        return Str::before(basename($this->file_path), '_');
    }

    public static function findByTranscodeToken(string $token): ?self
    {
        return static::where('file_path', 'like', "videos/{$token}_%")->first();
    }
}
