<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RestaurantTable extends Model
{
    use BelongsToTenant, HasFactory;

    protected $table = 'tables';

    protected $fillable = [
        'tenant_id',
        'table_number',
        'name',
        'section_id',
        'capacity',
        'status',
        'active_order_id',
        'is_active',
    ];

    protected $casts = [
        'capacity' => 'integer',
        'is_active' => 'boolean',
    ];

    public function section()
    {
        return $this->belongsTo(TableSection::class, 'section_id');
    }

    public function activeOrder()
    {
        return $this->belongsTo(Order::class, 'active_order_id');
    }

    public function isAvailable(): bool
    {
        return $this->status === 'available';
    }

    public function isOccupied(): bool
    {
        return $this->status === 'occupied';
    }
}
