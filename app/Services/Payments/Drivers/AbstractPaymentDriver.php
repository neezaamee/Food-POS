<?php

namespace App\Services\Payments\Drivers;

use App\Models\SystemSetting;
use App\Services\Payments\Contracts\PaymentGatewayInterface;
use Illuminate\Support\Facades\Log;

abstract class AbstractPaymentDriver implements PaymentGatewayInterface
{
    /**
     * Retrieve a setting value for this gateway
     */
    protected function getSetting(string $key, $default = null): ?string
    {
        return SystemSetting::get($key, $default);
    }

    /**
     * Clean and normalize a Pakistani phone number (e.g. 03001234567 or 923001234567)
     */
    protected function normalizePhoneNumber(string $phone): string
    {
        $clean = preg_replace('/[^0-9]/', '', $phone);

        if (str_starts_with($clean, '03')) {
            return $clean;
        }

        if (str_starts_with($clean, '923')) {
            return '0'.substr($clean, 2);
        }

        if (str_starts_with($clean, '3') && strlen($clean) === 10) {
            return '0'.$clean;
        }

        return $clean;
    }

    /**
     * Convert to international format with 92 prefix (e.g. 923001234567)
     */
    protected function toInternationalPhoneNumber(string $phone): string
    {
        $local = $this->normalizePhoneNumber($phone);

        if (str_starts_with($local, '03')) {
            return '92'.substr($local, 1);
        }

        return $local;
    }

    /**
     * Generate unique transaction reference
     */
    protected function generateReference(string $prefix, string $orderNumber): string
    {
        $cleanOrder = preg_replace('/[^A-Za-z0-9]/', '', $orderNumber);
        $random = strtoupper(substr(uniqid(), -5));

        return "{$prefix}-{$cleanOrder}-{$random}";
    }

    /**
     * Log payment event safely
     */
    protected function logInfo(string $message, array $context = []): void
    {
        Log::info("[Payment: {$this->getName()}] {$message}", $context);
    }

    protected function logError(string $message, array $context = []): void
    {
        Log::error("[Payment: {$this->getName()}] {$message}", $context);
    }
}
