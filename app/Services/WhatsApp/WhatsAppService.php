<?php

namespace App\Services\WhatsApp;

use App\Models\Deal;
use App\Models\Order;
use App\Models\SystemSetting;
use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppService
{
    protected string $bridgeUrl;

    public function __construct()
    {
        $this->bridgeUrl = rtrim(config('services.whatsapp.url', 'http://127.0.0.1:3333'), '/');
    }

    /**
     * Check if the background bridge Node.js service is reachable.
     */
    public function isServiceRunning(): bool
    {
        try {
            $response = Http::timeout(2)->get("{$this->bridgeUrl}/api/ping");

            return $response->successful() && ($response->json('ok') === true);
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Fetch WhatsApp connection state, paired user info, and active QR code.
     *
     * @return array{ok: bool, running: bool, connected: bool, state: string, user: ?array, qr: ?string, error?: string}
     */
    public function getStatus(): array
    {
        if (! $this->isServiceRunning()) {
            return [
                'ok' => false,
                'running' => false,
                'connected' => false,
                'state' => 'service_offline',
                'user' => null,
                'qr' => null,
                'error' => 'WhatsApp bridge service is not running on port 3333.',
            ];
        }

        try {
            $response = Http::timeout(4)->get("{$this->bridgeUrl}/api/status");
            if ($response->successful()) {
                $data = $response->json();

                return [
                    'ok' => true,
                    'running' => true,
                    'connected' => (bool) ($data['connected'] ?? false),
                    'state' => $data['state'] ?? 'disconnected',
                    'user' => $data['user'] ?? null,
                    'qr' => $data['qr'] ?? null,
                ];
            }
        } catch (Exception $e) {
            Log::warning('WhatsAppService getStatus error: '.$e->getMessage());
        }

        return [
            'ok' => false,
            'running' => false,
            'connected' => false,
            'state' => 'error',
            'user' => null,
            'qr' => null,
            'error' => 'Unable to read WhatsApp connection state.',
        ];
    }

    /**
     * Trigger session pairing and request fresh QR code.
     */
    public function triggerConnect(): array
    {
        if (! $this->isServiceRunning()) {
            return [
                'ok' => false,
                'error' => 'WhatsApp bridge service is offline. Please start it via `npm run whatsapp` or `php artisan whatsapp:serve`.',
            ];
        }

        try {
            $response = Http::timeout(6)->post("{$this->bridgeUrl}/api/connect");

            return $response->json() ?? ['ok' => true];
        } catch (Exception $e) {
            return ['ok' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Disconnect active WhatsApp session and clear auth credentials.
     */
    public function disconnect(): array
    {
        if (! $this->isServiceRunning()) {
            return ['ok' => false, 'error' => 'WhatsApp bridge service is offline.'];
        }

        try {
            $response = Http::timeout(6)->post("{$this->bridgeUrl}/api/logout");

            return $response->json() ?? ['ok' => true];
        } catch (Exception $e) {
            return ['ok' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Normalize international phone number for WhatsApp JID.
     */
    public function normalizePhoneNumber(?string $phone): ?string
    {
        if (empty($phone)) {
            return null;
        }

        // Keep only digits
        $digits = preg_replace('/\D+/', '', $phone);
        if (empty($digits)) {
            return null;
        }

        $defaultCountryCode = SystemSetting::get('whatsapp_default_country_code', '92');
        $defaultCountryCode = ltrim($defaultCountryCode, '+');

        // Handle Pakistan / local prefix patterns
        // E.g. 03001234567 -> 923001234567
        if (str_starts_with($digits, '0')) {
            $digits = $defaultCountryCode.substr($digits, 1);
        } elseif (strlen($digits) === 10 && str_starts_with($digits, '3')) {
            // E.g. 3001234567 -> 923001234567
            $digits = $defaultCountryCode.$digits;
        }

        return $digits;
    }

    /**
     * Format an order into a clean, WhatsApp markdown receipt string.
     */
    public function formatReceiptText(Order $order): string
    {
        $currency = SystemSetting::get('currency', 'Rs.');
        $restName = SystemSetting::get('restaurant_name', 'FOOD POINT');
        $tagline = SystemSetting::get('tagline');
        $address = SystemSetting::get('restaurant_address');
        $phone = SystemSetting::get('restaurant_phone');
        $footer = SystemSetting::get('whatsapp_receipt_footer', SystemSetting::get('invoice_footer_note', 'Thank you for dining with us! Please visit again.'));

        $lines = [];

        // Header
        $lines[] = '🍽️ *'.mb_strtoupper($restName).'*';
        if ($tagline) {
            $lines[] = "_{$tagline}_";
        }
        if ($address || $phone) {
            $metaParts = array_filter([$address, $phone ? "Tel: {$phone}" : null]);
            $lines[] = implode(' | ', $metaParts);
        }

        $lines[] = '━━━━━━━━━━━━━━━━━━━━';

        // Order Summary
        $lines[] = "🧾 *RECEIPT: #{$order->order_number}*";
        $lines[] = '📅 *Date:* '.($order->finalized_at ? $order->finalized_at->format('d/m/Y h:i A') : $order->created_at->format('d/m/Y h:i A'));
        $lines[] = "🏷️ *Type:* {$order->order_type}".($order->table_name ? " (Table: {$order->table_name})" : '');

        if ($order->customer_name) {
            $lines[] = "👤 *Customer:* {$order->customer_name}";
        }

        if ($order->order_type === 'DELIVERY') {
            if ($order->deliveryArea) {
                $lines[] = "📍 *Area:* {$order->deliveryArea->name}";
            }
            if ($order->customer_address) {
                $lines[] = "🏠 *Address:* {$order->customer_address}";
            }
            if ($order->rider) {
                $lines[] = "🛵 *Rider:* {$order->rider->name}";
            }
        }

        if ($order->fbr_invoice_number) {
            $lines[] = "🏛️ *FBR Inv:* {$order->fbr_invoice_number}";
        }

        $lines[] = '━━━━━━━━━━━━━━━━━━━━';
        $lines[] = '*ORDER ITEMS:*';

        // Items
        foreach ($order->items as $item) {
            $qty = (int) $item->quantity;
            $unitPrice = number_format((float) $item->unit_price, 2);
            $itemTotal = number_format((float) $item->subtotal, 2);
            $lines[] = "• {$qty}x *{$item->product_name}* ({$currency} {$unitPrice}) = *{$currency} {$itemTotal}*";

            // If Deal items exist
            $dealModel = $item->deal ?? Deal::where('product_id', $item->product_id)->with('items.product')->first();
            if ($dealModel && $dealModel->items->isNotEmpty()) {
                foreach ($dealModel->items as $dItem) {
                    $dealQty = (int) ($dItem->quantity * $item->quantity);
                    $dealProdName = $dItem->product?->name ?? 'Item';
                    $lines[] = "   ↳ {$dealQty}x {$dealProdName}";
                }
            }

            if ($item->notes) {
                $lines[] = "   _Note: {$item->notes}_";
            }
        }

        $lines[] = '━━━━━━━━━━━━━━━━━━━━';

        // Financials
        $lines[] = 'Subtotal: '.$currency.' '.number_format((float) $order->subtotal, 2);

        if ((float) $order->discount_amount > 0) {
            $lines[] = 'Discount: - '.$currency.' '.number_format((float) $order->discount_amount, 2);
        }

        if ((float) $order->delivery_charge > 0) {
            $lines[] = 'Delivery: + '.$currency.' '.number_format((float) $order->delivery_charge, 2);
        }

        if ((float) $order->tax_amount > 0) {
            $lines[] = 'Tax: + '.$currency.' '.number_format((float) $order->tax_amount, 2);
        }

        $lines[] = '━━━━━━━━━━━━━━━━━━━━';
        $lines[] = '💰 *TOTAL AMOUNT:* *'.$currency.' '.number_format((float) $order->grand_total, 2).'*';
        $lines[] = 'Paid Amount: '.$currency.' '.number_format((float) $order->paid_amount, 2);

        if ((float) $order->balance_amount > 0) {
            $lines[] = '⚠️ *Balance Due:* '.$currency.' '.number_format((float) $order->balance_amount, 2);
        }

        if (! empty($footer)) {
            $lines[] = '━━━━━━━━━━━━━━━━━━━━';
            $lines[] = "_{$footer}_";
        }

        return implode("\n", $lines);
    }

    /**
     * Send receipt message for an Order via connected WhatsApp.
     */
    public function sendReceipt(Order $order, ?string $recipientPhone = null): array
    {
        $targetPhone = $recipientPhone ?: $order->customer_phone;
        $normalizedPhone = $this->normalizePhoneNumber($targetPhone);

        if (! $normalizedPhone) {
            return [
                'ok' => false,
                'error' => 'No valid customer phone number provided for this order.',
            ];
        }

        $message = $this->formatReceiptText($order);

        return $this->sendMessage($normalizedPhone, $message);
    }

    /**
     * Send raw text message to normalized phone number via WhatsApp bridge.
     */
    public function sendMessage(string $phone, string $message): array
    {
        $normalizedPhone = $this->normalizePhoneNumber($phone);
        if (! $normalizedPhone) {
            return ['ok' => false, 'error' => 'Invalid phone number.'];
        }

        if (! $this->isServiceRunning()) {
            return [
                'ok' => false,
                'error' => 'WhatsApp bridge service is offline. Please start it on port 3333.',
                'fallback_url' => $this->getWhatsAppWebUrl($normalizedPhone, $message),
            ];
        }

        try {
            $response = Http::timeout(15)->post("{$this->bridgeUrl}/api/send", [
                'phone' => $normalizedPhone,
                'message' => $message,
            ]);

            $result = $response->json();
            if ($response->successful() && ($result['ok'] ?? false)) {
                return [
                    'ok' => true,
                    'messageId' => $result['messageId'] ?? null,
                    'recipient' => $normalizedPhone,
                ];
            }

            return [
                'ok' => false,
                'error' => $result['error'] ?? 'Failed to send WhatsApp message.',
                'fallback_url' => $this->getWhatsAppWebUrl($normalizedPhone, $message),
            ];
        } catch (Exception $e) {
            Log::error('WhatsAppService sendMessage exception: '.$e->getMessage());

            return [
                'ok' => false,
                'error' => 'WhatsApp send timeout or network error: '.$e->getMessage(),
                'fallback_url' => $this->getWhatsAppWebUrl($normalizedPhone, $message),
            ];
        }
    }

    /**
     * Generate instant WhatsApp Web direct link (wa.me) for zero-downtime manual fallback.
     */
    public function getWhatsAppWebUrl(string $phone, string $message): string
    {
        $cleanPhone = $this->normalizePhoneNumber($phone);

        return 'https://wa.me/'.$cleanPhone.'?text='.rawurlencode($message);
    }
}
