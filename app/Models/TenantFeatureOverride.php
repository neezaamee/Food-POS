<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TenantFeatureOverride extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'feature_key',
        'feature_code',
        'is_enabled',
        'notes',
    ];

    protected $casts = [
        'is_enabled' => 'boolean',
    ];

    /**
     * Alias feature_code to feature_key for convenience
     */
    public function getFeatureCodeAttribute(): string
    {
        return $this->feature_key;
    }

    public function setFeatureCodeAttribute(string $value): void
    {
        $this->attributes['feature_key'] = $value;
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function feature(): BelongsTo
    {
        return $this->belongsTo(PlanFeature::class, 'feature_key', 'key');
    }
}
