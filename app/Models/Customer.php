<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    use BelongsToTenant, HasFactory;

    protected $fillable = [
        'tenant_id',
        'name',
        'mobile',
        'email',
        'address',
        'area',
        'credit_limit',
        'opening_balance',
        'current_balance',
        'is_active',
    ];

    protected $casts = [
        'credit_limit' => 'decimal:2',
        'opening_balance' => 'decimal:2',
        'current_balance' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    protected $appends = ['balance', 'phone'];

    public function getBalanceAttribute(): float
    {
        return (float) $this->current_balance;
    }

    public function getPhoneAttribute(): ?string
    {
        return $this->mobile;
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function returns()
    {
        return $this->hasMany(SaleReturn::class);
    }

    /**
     * Calculate live net outstanding balance from opening balance, unpaid orders, and returns
     */
    public function calculateOutstandingBalance(): float
    {
        $opening = (float) $this->opening_balance;
        $unpaidInvoices = (float) $this->orders()
            ->where('order_status', '!=', 'cancelled')
            ->selectRaw('COALESCE(SUM(grand_total - paid_amount), 0) as diff')
            ->value('diff');
        $returns = (float) $this->returns()->sum('grand_total');

        return round($opening + $unpaidInvoices - $returns, 2);
    }

    /**
     * Recalculate and persist current balance
     */
    public function syncBalance(): float
    {
        $this->current_balance = $this->calculateOutstandingBalance();
        $this->save();

        return (float) $this->current_balance;
    }
}
