<?php

namespace App\Services\FBR;

use App\Models\AuditLog;
use App\Models\FbrSetting;
use App\Models\FbrSubmission;
use App\Models\Order;
use Exception;
use Illuminate\Support\Facades\Http;

class FbrService
{
    /**
     * Submit finalized order invoice to FBR Digital Invoicing API
     */
    public function submitInvoice(Order $order): FbrSubmission
    {
        $settings = FbrSetting::first();

        $submission = FbrSubmission::create([
            'order_id' => $order->id,
            'invoice_number' => $order->order_number,
            'status' => 'pending',
            'retry_count' => 0,
        ]);

        if (! $settings || ! $settings->is_enabled) {
            $submission->status = 'submitted';
            $submission->fbr_invoice_number = 'FBR-LOCAL-'.date('Ymd').'-'.rand(10000, 99999);
            $submission->response_payload = ['message' => 'FBR Sandbox / Local Simulation Mode active.'];
            $submission->save();

            $order->fbr_invoice_number = $submission->fbr_invoice_number;
            $order->save();

            return $submission;
        }

        $payload = [
            'POSID' => $settings->pos_id,
            'USIN' => $order->order_number,
            'DateTime' => $order->created_at->format('Y-m-d H:i:s'),
            'TotalSaleValue' => (float) $order->subtotal,
            'TotalQuantity' => (float) $order->items->sum('quantity'),
            'TotalTaxCharged' => (float) $order->tax_amount,
            'Discount' => (float) $order->discount_amount,
            'TotalBillAmount' => (float) $order->grand_total,
            'PaymentMode' => $order->payments->first()?->payment_method ?? 'Cash',
            'InvoiceType' => 1, // New Sale Invoice
            'Items' => $order->items->map(function ($item) {
                return [
                    'ItemCode' => $item->product_sku ?? (string) $item->product_id,
                    'ItemName' => $item->product_name,
                    'Quantity' => (float) $item->quantity,
                    'TotalAmount' => (float) $item->subtotal,
                    'SaleValue' => (float) $item->subtotal,
                    'TaxCharged' => (float) $item->tax_amount,
                ];
            })->toArray(),
        ];

        $submission->request_payload = $payload;

        try {
            $response = Http::withToken($settings->bearer_token)
                ->timeout(10)
                ->post($settings->api_url, $payload);

            $submission->http_status = $response->status();
            $submission->response_payload = $response->json();

            if ($response->successful()) {
                $submission->status = 'submitted';
                $submission->fbr_invoice_number = $response->json('InvoiceNumber') ?? ('FBR-'.time());
                $order->fbr_invoice_number = $submission->fbr_invoice_number;
                $order->save();
            } else {
                $submission->status = 'failed';
                $submission->error_message = $response->body();
            }
        } catch (Exception $e) {
            $submission->status = 'failed';
            $submission->error_message = $e->getMessage();
        }

        $submission->save();

        AuditLog::create([
            'user_id' => auth()->id(),
            'action' => 'FBR Invoice Submitted',
            'module' => 'FBR',
            'record_id' => $submission->id,
            'new_value' => ['status' => $submission->status, 'fbr_reference' => $submission->fbr_invoice_number],
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);

        return $submission;
    }
}
