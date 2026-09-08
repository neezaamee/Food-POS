/**
 * Food Point POS - Offline Engine & Connectivity Manager
 * Coordinates network detection, status badges, background sync, and offline actions.
 */
(function (window) {
    'use strict';

    const PosOfflineEngine = {
        isOnlineState: navigator.onLine,
        isSyncing: false,
        pingInterval: null,
        csrfToken: null,

        init() {
            this.csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

            // 1. Initial network listener
            window.addEventListener('online', () => this.handleConnectivityChange(true));
            window.addEventListener('offline', () => this.handleConnectivityChange(false));

            // 2. Periodic heartbeat ping (every 12 seconds to detect true internet vs dead WiFi)
            this.startHeartbeat();

            // 3. Prime / Refresh local catalog
            if (navigator.onLine) {
                this.downloadCatalog();
            }

            // 4. Update UI queue counter on load
            this.updateQueueBadge();

            console.log('⚡ Food Point POS Offline Engine initialized');
        },

        isOnline() {
            return this.isOnlineState;
        },

        startHeartbeat() {
            if (this.pingInterval) clearInterval(this.pingInterval);
            this.pingInterval = setInterval(async () => {
                try {
                    const res = await fetch('/pos/api/catalog', {
                        method: 'HEAD',
                        cache: 'no-store',
                        headers: { 'X-Requested-With': 'XMLHttpRequest' }
                    });
                    if (res.ok) {
                        if (!this.isOnlineState) {
                            this.handleConnectivityChange(true);
                        }
                    } else {
                        if (this.isOnlineState) {
                            this.handleConnectivityChange(false);
                        }
                    }
                } catch (err) {
                    if (this.isOnlineState) {
                        this.handleConnectivityChange(false);
                    }
                }
            }, 12000);
        },

        handleConnectivityChange(isOnline) {
            const previous = this.isOnlineState;
            this.isOnlineState = isOnline;

            this.updateStatusPill();

            if (isOnline && !previous) {
                console.log('🌐 Internet connection restored. Triggering auto-sync...');
                this.showToast('Internet connection restored! Syncing offline orders...', 'success');
                this.syncNow();
                this.downloadCatalog();
            } else if (!isOnline && previous) {
                console.warn('⚠️ Internet connection lost. Operating in Offline Mode.');
                this.showToast('Internet disconnected. Switched to Local Offline Mode.', 'warning');
            }
        },

        updateStatusPill() {
            const pill = document.getElementById('posNetworkStatusPill');
            if (!pill) return;

            if (this.isSyncing) {
                pill.className = 'badge bg-warning text-dark d-inline-flex align-items-center gap-1 px-2 py-1';
                pill.innerHTML = '<span class="spinner-border spinner-border-sm" style="width: 10px; height: 10px;"></span> Syncing...';
            } else if (this.isOnlineState) {
                pill.className = 'badge bg-success-subtle text-success border border-success-subtle d-inline-flex align-items-center gap-1 px-2 py-1';
                pill.innerHTML = '<span class="p-1 rounded-circle bg-success"></span> Online';
            } else {
                pill.className = 'badge bg-danger-subtle text-danger border border-danger-subtle d-inline-flex align-items-center gap-1 px-2 py-1';
                pill.innerHTML = '<i class="bi bi-wifi-off"></i> Offline Mode';
            }
        },

        async updateQueueBadge() {
            if (!window.PosOfflineDB) return;
            const count = await window.PosOfflineDB.getPendingCount();
            const badge = document.getElementById('posOfflineQueueBadge');
            const btn = document.getElementById('posOfflineSyncBtn');

            if (badge) {
                badge.innerText = count;
                badge.style.display = count > 0 ? 'inline-block' : 'none';
            }
            if (btn) {
                btn.style.display = count > 0 ? 'inline-flex' : 'none';
            }
        },

        /**
         * Fetch master catalog from server and cache into IndexedDB
         */
        async downloadCatalog() {
            try {
                const res = await fetch('/pos/api/catalog', {
                    headers: { 'Accept': 'application/json' }
                });
                if (res.ok) {
                    const data = await res.json();
                    if (data.success && window.PosOfflineDB) {
                        await window.PosOfflineDB.saveCatalog(data);
                        console.log('✅ Local POS Catalog cached in IndexedDB (' + (data.products?.length || 0) + ' items)');
                    }
                }
            } catch (err) {
                console.warn('Catalog download deferred:', err.message);
            }
        },

        /**
         * Upload pending offline orders to server
         */
        async syncNow() {
            if (this.isSyncing || !window.PosOfflineDB) return;

            const pending = await window.PosOfflineDB.getPendingOrders();
            if (!pending || pending.length === 0) {
                this.updateQueueBadge();
                return;
            }

            this.isSyncing = true;
            this.updateStatusPill();

            try {
                const res = await fetch('/pos/api/sync', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': this.csrfToken || ''
                    },
                    body: JSON.stringify({ orders: pending })
                });

                if (res.ok) {
                    const result = await res.json();
                    if (result.success && result.synced) {
                        await window.PosOfflineDB.markOrdersSynced(result.synced);
                        this.showToast(`Synced ${result.synced_count} offline order(s) successfully to cloud!`, 'success');
                    }
                } else {
                    console.error('Sync failed with HTTP ' + res.status);
                }
            } catch (err) {
                console.error('Sync error:', err);
            } finally {
                this.isSyncing = false;
                this.updateStatusPill();
                this.updateQueueBadge();
            }
        },

        /**
         * Display transient notification toast
         */
        showToast(message, type = 'info') {
            const toast = document.createElement('div');
            toast.className = `alert alert-${type} py-2 px-3 small position-fixed top-0 start-50 translate-middle-x mt-4 shadow-lg z-3`;
            toast.style.zIndex = '9999';
            toast.innerHTML = `
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-${type === 'success' ? 'check-circle-fill' : (type === 'danger' ? 'exclamation-octagon-fill' : 'info-circle-fill')}"></i>
                    <span>${message}</span>
                </div>
            `;
            document.body.appendChild(toast);
            setTimeout(() => toast.remove(), 4000);
        }
    };

    window.PosOfflineEngine = PosOfflineEngine;

    // Auto-boot on DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => PosOfflineEngine.init());
    } else {
        PosOfflineEngine.init();
    }
})(window);
