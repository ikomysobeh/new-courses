<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UserLevel extends Model
{
    use HasFactory;

    protected $fillable = [
        'legacy_id',
        'code',
        'name',
        'hierarchy_level',
        'can_manage_levels',
    ];

    protected $casts = [
        'can_manage_levels' => 'array',
    ];

    public function tiers(): HasMany
    {
        return $this->hasMany(UserLevelTier::class);
    }
}
