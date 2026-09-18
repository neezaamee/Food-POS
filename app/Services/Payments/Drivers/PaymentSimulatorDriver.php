<?php

namespace App\Services\Payments\Drivers;

class PaymentSimulatorDriver extends AbstractPaymentDriver
{
    public function getName(): string
    {
        return 'simulator';
    }

    public function getDisplayName(): string
    {
        return 'Payment Testing Sandbox Simulator';
    }

    public function isConfigured(): bool
    {
        return true; // Always enabled for testing purposes
    }

    public function isSandbox(): bool
    {
        return true;
    }

    public function initiatePushPayment(float $amount, string $mobileNumber, string $orderNumber, array $meta = []): array
    {
        $cleanPhone = $this->normalizePhoneNumber($mobileNumber);
        $targetProvider = $meta['simulated_provider'] ?? 'jazzcash';
        $prefix = strtoupper(substr($targetProvider, 0, 2)).'SIM';
        $txRef = $this->generateReference($prefix, $orderNumber);

        $this->logInfo("Simulator initiated push request for {$cleanPhone}, amount: Rs. {$amount}, provider: {$targetProvider}");

        return [
            'ok' => true,
            'transaction_id' => $txRef,
            'status' => 'pending_customer',
            'provider' => $targetProvider,
            'message' => "SIMULATOR: Push prompt dispatched to {$cleanPhone}. Click [Simulate Approved] below to approve with mock MPIN.",
            'data' => [
                'reference' => $txRef,
                'phone' => $cleanPhone,
                'amount' => $amount,
                'is_simulated' => true,
                'simulated_provider' => $targetProvider,
            ],
        ];
    }

    public function generateDynamicQr(float $amount, string $orderNumber, array $meta = []): array
    {
        $targetProvider = $meta['simulated_provider'] ?? 'raast';
        $prefix = strtoupper(substr($targetProvider, 0, 2)).'QR';
        $txRef = $this->generateReference($prefix, $orderNumber);

        // Simple mock SVG representation or payload
        $qrPayload = "SIMULATOR:FOODPOINT:ORDER:{$orderNumber}:AMOUNT:{$amount}:TID:{$txRef}";

        return [
            'ok' => true,
            'transaction_id' => $txRef,
            'provider' => $targetProvider,
            'qr_data' => $qrPayload,
            'message' => 'SIMULATOR: Dynamic QR generated for Rs. '.number_format($amount, 2).'. Click [Simulate Scanned & Paid] to complete.',
        ];
    }

    public function verifyTransaction(string $transactionRef): array
    {
        return [
            'ok' => true,
            'paid' => true,
            'status' => 'completed',
            'transaction_id' => $transactionRef,
            'message' => 'SIMULATOR: Transaction verified as successful.',
        ];
    }

    public function refundTransaction(string $transactionRef, float $amount): array
    {
        return [
            'ok' => true,
            'message' => "SIMULATOR: Reversal of Rs. {$amount} simulated successfully.",
            'refund_id' => 'SIM-RF-'.$transactionRef,
        ];
    }
}
