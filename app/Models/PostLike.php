<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PostLike extends Model
{
    use HasFactory;

    protected $table = 'post_likes';

    protected $fillable = [
        'legacy_id',
        'podcast_post_id',
        'user_id',
    ];

    public function post(): BelongsTo
    {
        return $this->belongsTo(PodcastPost::class, 'podcast_post_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
