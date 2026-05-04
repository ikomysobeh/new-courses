<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Audio extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'audios';

    protected $fillable = [
        'name',
        'description',
        'local_path',
        'duration',
        'thumbnail_path',
        'audio_category_id',
    ];

    protected function casts(): array
    {
        return [
            'duration' => 'integer',
            'deleted_at' => 'datetime',
        ];
    }

    public function audioCategory(): BelongsTo
    {
        return $this->belongsTo(AudioCategory::class);
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(AudioAssignment::class);
    }

    public function progress(): HasMany
    {
        return $this->hasMany(AudioProgress::class);
    }
}
