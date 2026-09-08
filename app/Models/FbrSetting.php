<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FbrSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'pos_id',
        'bearer_token',
        'api_url',
        'mode',
        'is_enabled',
    ];

    protected $casts = [
        'is_enabled' => 'boolean',
    ];
}
