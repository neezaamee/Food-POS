<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\SystemSetting;
use App\Services\WhatsApp\WhatsAppService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WhatsAppController extends Controller
{
    public function index(WhatsAppService $whatsAppService): View
    {
        $status = $whatsAppService->getStatus();
        $settings = [
            'country_code' => SystemSetting::get('whatsapp_default_country_code', '92'),
            'auto_send' => (bool) SystemSetting::get('whatsapp_auto_send', '0'),
            'footer' => SystemSetting::get('whatsapp_receipt_footer', SystemSetting::get('invoice_footer_note', 'Thank you for dining with us! Please visit again.')),
        ];

        return view('admin.whatsapp.index', compact('status', 'settings'));
    }

    /**
     * Polling endpoint for real-time QR code and connection state updates.
     */
    public function status(WhatsAppService $whatsAppService): JsonResponse
    {
        return response()->json($whatsAppService->getStatus());
    }

    /**
     * Request a new connection session / fresh QR code.
     */
    public function reconnect(WhatsAppService $whatsAppService): JsonResponse
    {
        $result = $whatsAppService->triggerConnect();

        return response()->json($result);
    }

    /**
     * Disconnect active WhatsApp session.
     */
    public function disconnect(WhatsAppService $whatsAppService): RedirectResponse
    {
        $result = $whatsAppService->disconnect();

        if (auth()->check()) {
            AuditLog::record(auth()->user(), 'whatsapp_disconnected', 'User unlinked WhatsApp session', 'WhatsApp');
        }

        if ($result['ok'] ?? false) {
            return redirect()->route('admin.whatsapp.index')->with('success', 'WhatsApp account disconnected successfully.');
        }

        return redirect()->route('admin.whatsapp.index')->with('error', $result['error'] ?? 'Failed to disconnect WhatsApp.');
    }

    /**
     * Send a test message to verify delivery.
     */
    public function test(Request $request, WhatsAppService $whatsAppService): JsonResponse
    {
        $request->validate([
            'phone' => 'required|string|min:7',
            'message' => 'nullable|string',
        ]);

        $phone = $request->input('phone');
        $message = $request->input('message') ?: '👋 Hello from *'.SystemSetting::get('restaurant_name', 'Food Point POS')."*!\n\nThis is a test message confirming your WhatsApp receipt dispatch integration is active and working perfectly. 🚀";

        $result = $whatsAppService->sendMessage($phone, $message);

        return response()->json($result);
    }

    /**
     * Save WhatsApp configuration settings.
     */
    public function updateSettings(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'whatsapp_default_country_code' => 'required|string|max:10',
            'whatsapp_auto_send' => 'nullable|boolean',
            'whatsapp_receipt_footer' => 'nullable|string|max:500',
        ]);

        try {
            SystemSetting::set('whatsapp_default_country_code', ltrim($validated['whatsapp_default_country_code'], '+'), 'whatsapp');
            SystemSetting::set('whatsapp_auto_send', $request->has('whatsapp_auto_send') ? '1' : '0', 'whatsapp');
            SystemSetting::set('whatsapp_receipt_footer', $validated['whatsapp_receipt_footer'] ?? '', 'whatsapp');

            if (auth()->check()) {
                AuditLog::record(auth()->user(), 'whatsapp_settings_updated', 'Updated WhatsApp preferences', 'WhatsApp');
            }

            return redirect()->route('admin.whatsapp.index')->with('success', 'WhatsApp settings saved successfully!');
        } catch (Exception $e) {
            return redirect()->route('admin.whatsapp.index')->with('error', 'Error saving settings: '.$e->getMessage());
        }
    }
}
