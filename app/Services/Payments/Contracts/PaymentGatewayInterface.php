<?php

namespace App\Services\Payments\Contracts;

interface PaymentGatewayInterface
{
    /**
     * Internal slug identifier for this provider (e.g. 'jazzcash', 'easypaisa')
     */
    public function getName(): string;

    /**
     * Human-friendly display label (e.g. 'JazzCash Mobile Account')
     */
    public function getDisplayName(): string;

    /**
     * Check whether this gateway is enabled and has requisite credentials configured
     */
    public function isConfigured(): bool;

    /**
     * Check whether gateway is operating in sandbox/test mode
     */
    public function isSandbox(): bool;

    /**
     * Initiate a real-time mobile push payment prompt (USSD / App alert)
     *
     * @return array{ok: bool, transaction_id: string, status: string, message: string, data?: array}
     */
    public function initiatePushPayment(float $amount, string $mobileNumber, string $orderNumber, array $meta = []): array;

    /**
     * Generate dynamic QR payload for customer scan
     *
     * @return array{ok: bool, transaction_id: string, qr_data: string, qr_svg?: string, message: string}
     */
    public function generateDynamicQr(float $amount, string $orderNumber, array $meta = []): array;

    /**
     * Verify payment status with the gateway
     *
     * @return array{ok: bool, paid: bool, status: string, transaction_id: string, message: string}
     */
    public function verifyTransaction(string $transactionRef): array;

    /**
     * Issue refund or reversal for a previous payment
     *
     * @return array{ok: bool, message: string, refund_id?: string}
     */
    public function refundTransaction(string $transactionRef, float $amount): array;
}
