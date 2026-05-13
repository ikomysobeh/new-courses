<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EvaluationConfig extends Model
{
    protected $fillable = [
        'name',
        'max_score',
        'applies_to',
    ];

    public function types(): HasMany
    {
        return $this->hasMany(EvaluationType::class);
    }

    // ---- Scopes ----

    public function scopeForRegular($query)
    {
        return $query->whereIn('applies_to', ['regular', 'both']);
    }

    public function scopeForOnline($query)
    {
        return $query->whereIn('applies_to', ['online', 'both']);
    }

    public function scopeForBoth($query)
    {
        return $query->where('applies_to', 'both');
    }

    // ---- Helpers ----

    /**
     * Whether this config applies to the given course type.
     */
    public function appliesTo(string $courseType): bool
    {
        return $this->applies_to === 'both' || $this->applies_to === $courseType;
    }
}
