<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FbrSubmission extends Model
{
    use BelongsToTenant, HasFactory;

    protected $fillable = [
        'tenant_id',
        'order_id',
        'invoice_number',
        'fbr_invoice_number',
        'http_status',
        'request_payload',
        'response_payload',
        'status',
        'error_message',
        'retry_count',
    ];

    protected $casts = [
        'request_payload' => 'array',
        'response_payload' => 'array',
        'http_status' => 'integer',
        'retry_count' => 'integer',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
