@extends('layouts.app', ['title' => 'WhatsApp Receipt Integration - Food Point POS'])

@section('content')
<div class="row g-4 mb-4">
    <div class="col-12 d-flex flex-wrap align-items-center justify-content-between gap-3">
        <div>
            <h4 class="mb-1 fw-bold text-body-emphasis d-flex align-items-center gap-2">
                <i class="bi bi-whatsapp text-success fs-3"></i> WhatsApp Receipt Integration
            </h4>
            <p class="text-muted mb-0 small">Connect your WhatsApp account to automatically dispatch digital receipts to customer WhatsApp numbers</p>
        </div>
        <div class="d-flex align-items-center gap-2">
            <button type="button" class="btn btn-outline-primary btn-sm" id="btnRefreshStatus">
                <i class="bi bi-arrow-clockwise me-1"></i> Refresh Status
            </button>
        </div>
    </div>
</div>

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show border-0 shadow-sm" role="alert">
    <i class="bi bi-check-circle-fill me-2"></i>{{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

@if(session('error'))
<div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm" role="alert">
    <i class="bi bi-exclamation-triangle-fill me-2"></i>{{ session('error') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

<div class="row g-4">
    <!-- Left Column: WhatsApp Pairing & Status Card -->
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm rounded-3 h-100">
            <div class="card-header bg-transparent border-0 pt-4 px-4 pb-0 d-flex align-items-center justify-content-between">
                <h6 class="fw-bold mb-0"><i class="bi bi-qr-code-scan text-primary me-2"></i> WhatsApp Device Connection</h6>
                <span id="connectionBadge" class="badge bg-secondary px-3 py-2 fs-7">
                    <span class="spinner-border spinner-border-sm me-1" role="status"></span> Checking...
                </span>
            </div>
            <div class="card-body p-4 text-center">

                <!-- Service Offline State -->
                <div id="serviceOfflineAlert" class="alert alert-warning text-start border-0 shadow-sm d-none" role="alert">
                    <div class="d-flex gap-2">
                        <i class="bi bi-exclamation-octagon-fill fs-4 text-warning flex-shrink-0"></i>
                        <div>
                            <h6 class="fw-bold mb-1">WhatsApp Bridge Service is Offline</h6>
                            <p class="small mb-2 text-secondary">The local background Node.js bridge service is not running on port 3333. Please start it using terminal:</p>
                            <div class="p-2 bg-dark text-white rounded font-monospace small mb-2 user-select-all">
                                php artisan whatsapp:serve
                            </div>
                            <span class="small text-muted">Or run: <code class="user-select-all">npm run whatsapp</code> in your Food-POS project directory.</span>
                        </div>
                    </div>
                </div>

                <!-- Connected State Container -->
                <div id="connectedState" class="d-none py-4">
                    <div class="mb-3">
                        <div class="d-inline-flex align-items-center justify-content-center bg-success bg-opacity-10 text-success rounded-circle" style="width: 80px; height: 80px;">
                            <i class="bi bi-whatsapp fs-1"></i>
                        </div>
                    </div>
                    <h5 class="fw-bold text-success mb-1">WhatsApp Connected!</h5>
                    <p class="text-muted small mb-3">Your device is actively linked and ready to send order receipts directly to customers.</p>
                    
                    <div class="p-3 bg-light rounded-3 text-start mx-auto mb-4 border" style="max-width: 380px;">
                        <div class="d-flex justify-content-between py-1 border-bottom">
                            <span class="text-muted small">Connected Number:</span>
                            <strong id="connectedPhone" class="small text-body-emphasis">+--</strong>
                        </div>
                        <div class="d-flex justify-content-between py-1 border-bottom">
                            <span class="text-muted small">Device Name:</span>
                            <strong id="connectedName" class="small text-body-emphasis">Food Point POS</strong>
                        </div>
                        <div class="d-flex justify-content-between py-1">
                            <span class="text-muted small">Status:</span>
                            <span class="badge bg-success bg-opacity-10 text-success px-2 py-1 small">Active & Linked</span>
                        </div>
                    </div>

                    <form action="{{ route('admin.whatsapp.disconnect') }}" method="POST" onsubmit="return confirm('Are you sure you want to disconnect this WhatsApp session? You will need to scan QR code again.');">
                        @csrf
                        <button type="submit" class="btn btn-outline-danger btn-sm px-4">
                            <i class="bi bi-box-arrow-right me-1"></i> Disconnect WhatsApp Session
                        </button>
                    </form>
                </div>

                <!-- QR Pairing State Container -->
                <div id="qrPairingState" class="d-none">
                    <p class="text-muted small mb-3">Scan this QR code using WhatsApp on your phone to link your restaurant's WhatsApp account.</p>

                    <!-- QR Code Frame -->
                    <div class="p-3 bg-white border rounded-3 d-inline-block shadow-sm mb-3 position-relative" style="min-width: 260px; min-height: 260px;">
                        <img id="qrCodeImage" src="" alt="WhatsApp QR Code" class="img-fluid rounded" style="max-width: 260px; height: auto;">
                        <div id="qrLoadingSpinner" class="position-absolute top-50 start-50 translate-middle text-center d-none">
                            <div class="spinner-border text-primary" role="status"></div>
                            <div class="small text-muted mt-2 fw-semibold">Generating QR...</div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <button type="button" class="btn btn-sm btn-light border px-3" id="btnRefreshQr">
                            <i class="bi bi-arrow-repeat me-1"></i> Request Fresh QR Code
                        </button>
                    </div>

                    <!-- Instructions -->
                    <div class="text-start bg-light rounded-3 p-3 mx-auto border" style="max-width: 440px;">
                        <h6 class="fw-bold small mb-2"><i class="bi bi-info-circle text-primary me-1"></i> How to link:</h6>
                        <ol class="small text-secondary mb-0 ps-3">
                            <li class="mb-1">Open <strong>WhatsApp</strong> on your phone</li>
                            <li class="mb-1">Tap <strong>Menu (⋮)</strong> on Android or <strong>Settings</strong> on iPhone</li>
                            <li class="mb-1">Tap <strong>Linked Devices</strong> &rarr; <strong>Link a Device</strong></li>
                            <li>Point your camera at this QR code to scan</li>
                        </ol>
                    </div>
                </div>

                <!-- Initializing / Loading State -->
                <div id="loadingState" class="py-5">
                    <div class="spinner-border text-primary mb-3" role="status"></div>
                    <h6 class="text-muted fw-normal">Checking WhatsApp bridge connection...</h6>
                </div>

            </div>
        </div>
    </div>

    <!-- Right Column: Settings & Test Tool -->
    <div class="col-lg-6">
        <div class="row g-4">
            <!-- Test WhatsApp Receipt Card -->
            <div class="col-12">
                <div class="card border-0 shadow-sm rounded-3">
                    <div class="card-header bg-transparent border-0 pt-4 px-4 pb-0">
                        <h6 class="fw-bold mb-0"><i class="bi bi-send-check text-success me-2"></i> Test Message Dispatcher</h6>
                    </div>
                    <div class="card-body p-4">
                        <p class="text-muted small mb-3">Send an instant test message to any WhatsApp number to verify your connection.</p>
                        
                        <div class="mb-3">
                            <label class="form-label small fw-medium">Recipient Phone Number</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light text-muted small"><i class="bi bi-telephone"></i></span>
                                <input type="text" id="testPhone" class="form-control" placeholder="03001234567 or 923001234567" value="">
                            </div>
                            <div class="form-text small text-muted">Local format with 0 or international format accepted.</div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-medium">Message Body (Optional)</label>
                            <textarea id="testMessage" class="form-control form-control-sm" rows="2" placeholder="Leave empty for standard test message..."></textarea>
                        </div>

                        <button type="button" id="btnSendTest" class="btn btn-success btn-sm px-3">
                            <i class="bi bi-send me-1"></i> Send Test Message
                        </button>

                        <div id="testResultAlert" class="mt-3 d-none"></div>
                    </div>
                </div>
            </div>

            <!-- WhatsApp Configuration Settings Card -->
            <div class="col-12">
                <div class="card border-0 shadow-sm rounded-3">
                    <div class="card-header bg-transparent border-0 pt-4 px-4 pb-0">
                        <h6 class="fw-bold mb-0"><i class="bi bi-sliders text-primary me-2"></i> WhatsApp Dispatch Preferences</h6>
                    </div>
                    <div class="card-body p-4">
                        <form action="{{ route('admin.whatsapp.settings') }}" method="POST">
                            @csrf

                            <div class="row g-3 mb-3">
                                <div class="col-md-6">
                                    <label class="form-label small fw-medium">Default Country Code</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-light text-muted small">+</span>
                                        <input type="text" name="whatsapp_default_country_code" class="form-control" value="{{ $settings['country_code'] }}" required>
                                    </div>
                                    <div class="form-text small text-muted">Default for local numbers (e.g. 92 for Pakistan).</div>
                                </div>

                                <div class="col-md-6 d-flex align-items-center">
                                    <div class="form-check form-switch mt-3">
                                        <input class="form-check-input" type="checkbox" name="whatsapp_auto_send" value="1" id="autoSendCheck" {{ $settings['auto_send'] ? 'checked' : '' }}>
                                        <label class="form-check-label small fw-medium" for="autoSendCheck">
                                            Auto-send on Checkout
                                        </label>
                                        <div class="text-muted" style="font-size: 11px;">Automatically sends receipt when order is finalized with a customer phone.</div>
                                    </div>
                                </div>

                                <div class="col-12">
                                    <label class="form-label small fw-medium">Custom Receipt Footer Note</label>
                                    <input type="text" name="whatsapp_receipt_footer" class="form-control" value="{{ $settings['footer'] }}" placeholder="Thank you for dining with us! Please visit again.">
                                    <div class="form-text small text-muted">Appears at the very bottom of every WhatsApp receipt.</div>
                                </div>
                            </div>

                            <div class="d-flex justify-content-end">
                                <button type="submit" class="btn btn-primary px-4 btn-sm">
                                    <i class="bi bi-floppy-fill me-1"></i> Save Preferences
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const statusUrl = "{{ route('admin.whatsapp.status') }}";
    const reconnectUrl = "{{ route('admin.whatsapp.reconnect') }}";
    const testUrl = "{{ route('admin.whatsapp.test') }}";

    const badge = document.getElementById('connectionBadge');
    const loadingState = document.getElementById('loadingState');
    const offlineAlert = document.getElementById('serviceOfflineAlert');
    const connectedState = document.getElementById('connectedState');
    const qrPairingState = document.getElementById('qrPairingState');
    const qrImage = document.getElementById('qrCodeImage');
    const qrSpinner = document.getElementById('qrLoadingSpinner');
    const connectedPhone = document.getElementById('connectedPhone');
    const connectedName = document.getElementById('connectedName');
    const btnRefreshStatus = document.getElementById('btnRefreshStatus');
    const btnRefreshQr = document.getElementById('btnRefreshQr');
    const btnSendTest = document.getElementById('btnSendTest');
    const testPhone = document.getElementById('testPhone');
    const testMessage = document.getElementById('testMessage');
    const testResultAlert = document.getElementById('testResultAlert');

    let pollInterval = null;

    function renderStatus(data) {
        loadingState.classList.add('d-none');

        if (!data.running) {
            offlineAlert.classList.remove('d-none');
            connectedState.classList.add('d-none');
            qrPairingState.classList.add('d-none');
            badge.className = 'badge bg-danger px-3 py-2 fs-7';
            badge.innerHTML = '<i class="bi bi-x-circle me-1"></i> Service Offline';
            return;
        }

        offlineAlert.classList.add('d-none');

        if (data.connected) {
            badge.className = 'badge bg-success px-3 py-2 fs-7';
            badge.innerHTML = '<i class="bi bi-check-circle-fill me-1"></i> WhatsApp Connected';
            connectedState.classList.remove('d-none');
            qrPairingState.classList.add('d-none');
            connectedPhone.textContent = data.user ? ('+' + data.user.phone) : 'Linked Device';
            if (data.user && data.user.name) {
                connectedName.textContent = data.user.name;
            }
        } else if (data.qr) {
            badge.className = 'badge bg-warning text-dark px-3 py-2 fs-7';
            badge.innerHTML = '<i class="bi bi-qr-code me-1"></i> Scan QR to Connect';
            connectedState.classList.add('d-none');
            qrPairingState.classList.remove('d-none');
            qrImage.src = data.qr;
            qrImage.classList.remove('d-none');
            qrSpinner.classList.add('d-none');
        } else {
            badge.className = 'badge bg-secondary px-3 py-2 fs-7';
            badge.innerHTML = '<i class="bi bi-arrow-repeat me-1"></i> Connecting...';
            connectedState.classList.add('d-none');
            qrPairingState.classList.remove('d-none');
            qrSpinner.classList.remove('d-none');
            qrImage.classList.add('d-none');
        }
    }

    async function fetchStatus() {
        try {
            const res = await fetch(statusUrl, {
                headers: { 'Accept': 'application/json' }
            });
            const data = await res.json();
            renderStatus(data);
        } catch (err) {
            console.error('Error fetching WhatsApp status:', err);
        }
    }

    // Polling every 2.5 seconds to detect live QR scan automatically
    fetchStatus();
    pollInterval = setInterval(fetchStatus, 2500);

    btnRefreshStatus.addEventListener('click', function () {
        badge.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Checking...';
        fetchStatus();
    });

    btnRefreshQr.addEventListener('click', async function () {
        qrSpinner.classList.remove('d-none');
        qrImage.classList.add('d-none');
        try {
            await fetch(reconnectUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                }
            });
            setTimeout(fetchStatus, 1500);
        } catch (err) {
            console.error('Error requesting new QR:', err);
        }
    });

    btnSendTest.addEventListener('click', async function () {
        const phone = testPhone.value.trim();
        if (!phone) {
            alert('Please enter a test phone number.');
            return;
        }

        btnSendTest.disabled = true;
        btnSendTest.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Sending...';
        testResultAlert.className = 'alert alert-info border-0 shadow-sm small';
        testResultAlert.textContent = 'Dispatching test message via WhatsApp bridge...';
        testResultAlert.classList.remove('d-none');

        try {
            const res = await fetch(testUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    phone: phone,
                    message: testMessage.value.trim()
                })
            });

            const result = await res.json();
            if (result.ok) {
                testResultAlert.className = 'alert alert-success border-0 shadow-sm small';
                testResultAlert.innerHTML = '<i class="bi bi-check-circle-fill me-1"></i> Message sent successfully to +' + (result.recipient || phone) + '!';
            } else {
                testResultAlert.className = 'alert alert-danger border-0 shadow-sm small';
                let errMsg = '<i class="bi bi-exclamation-triangle-fill me-1"></i> Failed to send: ' + (result.error || 'Unknown error');
                if (result.fallback_url) {
                    errMsg += '<br><a href="' + result.fallback_url + '" target="_blank" class="btn btn-sm btn-outline-success mt-2"><i class="bi bi-whatsapp me-1"></i> Open via WhatsApp Web</a>';
                }
                testResultAlert.innerHTML = errMsg;
            }
        } catch (err) {
            testResultAlert.className = 'alert alert-danger border-0 shadow-sm small';
            testResultAlert.innerHTML = '<i class="bi bi-exclamation-triangle-fill me-1"></i> Network error: ' + err.message;
        } finally {
            btnSendTest.disabled = false;
            btnSendTest.innerHTML = '<i class="bi bi-send me-1"></i> Send Test Message';
        }
    });
});
</script>
@endpush
