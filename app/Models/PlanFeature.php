<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PlanFeature extends Model
{
    use HasFactory;

    protected $fillable = [
        'key',
        'name',
        'group',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Alias code to key for convenience
     */
    public function getCodeAttribute(): string
    {
        return $this->key;
    }

    public function overrides(): HasMany
    {
        return $this->hasMany(TenantFeatureOverride::class, 'feature_key', 'key');
    }
}
