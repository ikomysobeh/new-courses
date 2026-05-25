<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Str;

class PodcastPost extends Model
{
    use HasFactory;

    protected $table = 'podcast_posts';

    protected $fillable = [
        'title',
        'slug',
        'excerpt',
        'description',
        'mediable_type',
        'mediable_id',
        'thumbnail_path',
        'status',
        'published_at',
        'tags',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'tags'         => 'array',
            'published_at' => 'datetime',
        ];
    }

    // -------------------------------------------------------------------------
    // Relations
    // -------------------------------------------------------------------------

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function mediable(): MorphTo
    {
        return $this->morphTo();
    }

    public function comments(): HasMany
    {
        return $this->hasMany(PostComment::class, 'podcast_post_id');
    }

    public function likes(): HasMany
    {
        return $this->hasMany(PostLike::class, 'podcast_post_id');
    }

    // -------------------------------------------------------------------------
    // Scopes
    // -------------------------------------------------------------------------

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', 'published');
    }

    public function scopeDraft(Builder $query): Builder
    {
        return $query->where('status', 'draft');
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    public static function generateUniqueSlug(string $title, ?int $excludeId = null): string
    {
        $base  = Str::slug($title);
        $slug  = $base;
        $count = 1;

        while (
            static::where('slug', $slug)
                ->when($excludeId, fn (Builder $q) => $q->where('id', '!=', $excludeId))
                ->exists()
        ) {
            $slug = $base . '-' . $count++;
        }

        return $slug;
    }
}
