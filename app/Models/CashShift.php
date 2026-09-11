<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CashShift extends Model
{
    use BelongsToTenant, HasFactory;

    protected $fillable = [
        'tenant_id',
        'user_id',
        'opened_at',
        'opening_cash',
        'cash_sales',
        'cash_receipts',
        'cash_payments',
        'refunds',
        'expected_cash',
        'actual_cash',
        'difference',
        'closed_at',
        'status',
        'notes',
    ];

    protected $casts = [
        'opened_at' => 'datetime',
        'closed_at' => 'datetime',
        'opening_cash' => 'decimal:2',
        'cash_sales' => 'decimal:2',
        'cash_receipts' => 'decimal:2',
        'cash_payments' => 'decimal:2',
        'refunds' => 'decimal:2',
        'expected_cash' => 'decimal:2',
        'actual_cash' => 'decimal:2',
        'difference' => 'decimal:2',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function transactions()
    {
        return $this->hasMany(CashTransaction::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class, 'cash_shift_id');
    }

    public function totalSales(): float
    {
        return (float) $this->orders()->where('order_status', 'completed')->sum('grand_total');
    }

    public function unpaidOrders()
    {
        return $this->orders()
            ->where('payment_status', '!=', 'paid')
            ->where('order_status', '!=', 'cancelled');
    }

    public function unpaidOrdersCount(): int
    {
        return $this->unpaidOrders()->count();
    }

    public function isOpen(): bool
    {
        return $this->status === 'open';
    }
}
