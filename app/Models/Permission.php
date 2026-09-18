<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Permission extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'slug', 'module'];

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'role_permission');
    }

    public function scopeByModule(Builder $query, string $module): Builder
    {
        return $query->where('module', $module);
    }

    /**
     * Get all permissions grouped logically by functional module
     *
     * @return \Illuminate\Support\Collection<string, Collection<int, Permission>>
     */
    public static function groupedByModule(): \Illuminate\Support\Collection
    {
        $order = [
            'POS' => 1,
            'Orders' => 2,
            'Cash' => 3,
            'Restaurant' => 4,
            'Delivery' => 5,
            'Catalog' => 6,
            'Resources' => 7,
            'Inventory' => 8,
            'Finance' => 9,
            'Reports' => 10,
            'Admin' => 11,
        ];

        return static::all()
            ->sortBy(fn ($perm) => $order[$perm->module] ?? 99)
            ->groupBy('module');
    }
}
