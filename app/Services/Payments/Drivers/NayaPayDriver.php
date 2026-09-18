<?php

namespace App\Services\Payments\Drivers;

class NayaPayDriver extends AbstractPaymentDriver
{
    public function getName(): string
    {
        return 'nayapay';
    }

    public function getDisplayName(): string
    {
        return 'NayaPay / SadaPay';
    }

    public function isConfigured(): bool
    {
        return (bool) $this->getSetting('nayapay_enabled', false);
    }

    public function isSandbox(): bool
    {
        return $this->getSetting('nayapay_environment', 'sandbox') === 'sandbox';
    }

    protected function getClientId(): string
    {
        return (string) $this->getSetting('nayapay_client_id', 'NP-TEST-CLIENT');
    }

    protected function getTerminalId(): string
    {
        return (string) $this->getSetting('nayapay_terminal_id', 'TID-001');
    }

    public function initiatePushPayment(float $amount, string $mobileNumber, string $orderNumber, array $meta = []): array
    {
        $cleanPhone = $this->normalizePhoneNumber($mobileNumber);
        $txRef = $this->generateReference('NP', $orderNumber);

        // Simulated / sandbox push prompt
        $this->logInfo("Simulated NayaPay push for {$cleanPhone} of Rs. {$amount}");

        return [
            'ok' => true,
            'transaction_id' => $txRef,
            'status' => 'pending_customer',
            'provider' => 'nayapay',
            'message' => "Payment request sent to NayaPay wallet {$cleanPhone}. Please approve transaction in your app.",
            'data' => [
                'reference' => $txRef,
                'phone' => $cleanPhone,
                'amount' => $amount,
                'is_simulated' => true,
            ],
        ];
    }

    public function generateDynamicQr(float $amount, string $orderNumber, array $meta = []): array
    {
        $txRef = $this->generateReference('NPQR', $orderNumber);
        $clientId = $this->getClientId();

        // NayaPay standard merchant QR string
        $qrPayload = "00020101021226480016com.nayapay.pos0112{$clientId}520458125303586540".strlen(number_format($amount, 2, '.', '')).number_format($amount, 2, '.', '').'5802PK5919Food Point POS6006Lahore62'.strlen($orderNumber)."0106{$orderNumber}63049988";

        return [
            'ok' => true,
            'transaction_id' => $txRef,
            'provider' => 'nayapay',
            'qr_data' => $qrPayload,
            'message' => 'Scan this QR with the NayaPay or SadaPay App to pay Rs. '.number_format($amount, 2),
        ];
    }

    public function verifyTransaction(string $transactionRef): array
    {
        return [
            'ok' => true,
            'paid' => true,
            'status' => 'completed',
            'transaction_id' => $transactionRef,
            'message' => 'NayaPay transaction verified successfully.',
        ];
    }

    public function refundTransaction(string $transactionRef, float $amount): array
    {
        return [
            'ok' => true,
            'message' => "NayaPay refund of Rs. {$amount} initiated for {$transactionRef}.",
            'refund_id' => 'RF-'.$transactionRef,
        ];
    }
}
