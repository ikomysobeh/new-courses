<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UserLevelTier extends Model
{
    use HasFactory;

    protected $fillable = [
        'legacy_id',
        'user_level_id',
        'tier_name',
        'tier_order',
    ];

    public function userLevel(): BelongsTo
    {
        return $this->belongsTo(UserLevel::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}
