<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SystemSetting extends Model
{
    use HasFactory;

    protected $fillable = ['key', 'value', 'group'];

    /**
     * Retrieve a setting value with optional fallback key aliasing
     */
    public static function get(string $key, $default = null): ?string
    {
        $setting = static::where('key', $key)->first();
        if ($setting && $setting->value !== null && $setting->value !== '') {
            return $setting->value;
        }

        // Backward compatibility key aliases
        $aliases = [
            'restaurant_name' => 'business_name',
            'business_name' => 'restaurant_name',
            'restaurant_address' => 'business_address',
            'business_address' => 'restaurant_address',
            'restaurant_phone' => 'business_phone',
            'business_phone' => 'restaurant_phone',
            'currency' => 'currency_symbol',
            'currency_symbol' => 'currency',
            'tax_rate' => 'tax_rate_percent',
            'tax_rate_percent' => 'tax_rate',
            'invoice_footer_note' => 'receipt_footer_note',
            'receipt_footer_note' => 'invoice_footer_note',
        ];

        if (isset($aliases[$key])) {
            $fallback = static::where('key', $aliases[$key])->first();
            if ($fallback && $fallback->value !== null && $fallback->value !== '') {
                return $fallback->value;
            }
        }

        return $default;
    }

    public static function set(string $key, $value, string $group = 'general')
    {
        return static::updateOrCreate(['key' => $key], ['value' => $value, 'group' => $group]);
    }

    /**
     * Get active restaurant logo URL or default fallback
     */
    public static function logoUrl(): string
    {
        $logo = static::where('key', 'restaurant_logo')->value('value');
        if ($logo && file_exists(public_path($logo))) {
            return asset($logo);
        }

        return asset('assets/img/logo.webp');
    }
}
