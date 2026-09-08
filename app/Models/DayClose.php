<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DayClose extends Model
{
    use HasFactory;

    protected $fillable = [
        'business_date',
        'closed_by',
        'total_shifts_count',
        'total_orders_count',
        'gross_sales',
        'discount_amount',
        'tax_amount',
        'delivery_charges',
        'net_sales',
        'cash_sales',
        'digital_sales',
        'credit_sales',
        'total_refunds',
        'opening_cash_total',
        'expected_cash_total',
        'actual_cash_total',
        'difference_total',
        'shift_ids',
        'notes',
        'closed_at',
    ];

    protected $casts = [
        'business_date' => 'date',
        'closed_at' => 'datetime',
        'gross_sales' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'delivery_charges' => 'decimal:2',
        'net_sales' => 'decimal:2',
        'cash_sales' => 'decimal:2',
        'digital_sales' => 'decimal:2',
        'credit_sales' => 'decimal:2',
        'total_refunds' => 'decimal:2',
        'opening_cash_total' => 'decimal:2',
        'expected_cash_total' => 'decimal:2',
        'actual_cash_total' => 'decimal:2',
        'difference_total' => 'decimal:2',
        'shift_ids' => 'array',
    ];

    public function closedByUser()
    {
        return $this->belongsTo(User::class, 'closed_by');
    }

    public function shifts()
    {
        return CashShift::whereIn('id', $this->shift_ids ?? [])->get();
    }
}
