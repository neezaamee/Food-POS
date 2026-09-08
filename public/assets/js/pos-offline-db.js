/**
 * Food Point POS - Client-Side IndexedDB Storage Manager
 * Stores local product catalog, tables, settings, active shift, and queued offline orders.
 */
(function (window) {
    'use strict';

    const DB_NAME = 'FoodPointPOS_DB';
    const DB_VERSION = 1;
    let dbInstance = null;

    const PosOfflineDB = {
        /**
         * Open or upgrade IndexedDB
         */
        async openDB() {
            if (dbInstance) {
                return dbInstance;
            }

            return new Promise((resolve, reject) => {
                const request = indexedDB.open(DB_NAME, DB_VERSION);

                request.onupgradeneeded = function (event) {
                    const db = event.target.result;

                    // Store 1: Catalog cache (single object or keyed stores)
                    if (!db.objectStoreNames.contains('catalog')) {
                        db.createObjectStore('catalog', { keyPath: 'key' });
                    }

                    // Store 2: Queued Offline Orders
                    if (!db.objectStoreNames.contains('offline_orders')) {
                        const orderStore = db.createObjectStore('offline_orders', { keyPath: 'client_uuid' });
                        orderStore.createIndex('status', 'status', { unique: false });
                        orderStore.createIndex('created_at', 'created_at', { unique: false });
                    }

                    // Store 3: Local Sequence Counters (e.g. daily offline order sequence)
                    if (!db.objectStoreNames.contains('counters')) {
                        db.createObjectStore('counters', { keyPath: 'key' });
                    }
                };

                request.onsuccess = function (event) {
                    dbInstance = event.target.result;
                    resolve(dbInstance);
                };

                request.onerror = function (event) {
                    console.error('IndexedDB open error:', event.target.error);
                    reject(event.target.error);
                };
            });
        },

        /**
         * Save full catalog into IndexedDB
         */
        async saveCatalog(catalogData) {
            const db = await this.openDB();
            return new Promise((resolve, reject) => {
                const tx = db.transaction('catalog', 'readwrite');
                const store = tx.objectStore('catalog');

                store.put({ key: 'products', data: catalogData.products || [] });
                store.put({ key: 'categories', data: catalogData.categories || [] });
                store.put({ key: 'sections', data: catalogData.sections || [] });
                store.put({ key: 'delivery_areas', data: catalogData.delivery_areas || [] });
                store.put({ key: 'settings', data: catalogData.settings || {} });
                store.put({ key: 'active_shift', data: catalogData.active_shift || null });
                store.put({ key: 'last_synced_at', data: new Date().toISOString() });

                tx.oncomplete = () => resolve(true);
                tx.onerror = (e) => reject(e.target.error);
            });
        },

        /**
         * Retrieve cached catalog data
         */
        async getCatalogItem(key) {
            const db = await this.openDB();
            return new Promise((resolve, reject) => {
                const tx = db.transaction('catalog', 'readonly');
                const store = tx.objectStore('catalog');
                const request = store.get(key);

                request.onsuccess = () => resolve(request.result ? request.result.data : null);
                request.onerror = (e) => reject(e.target.error);
            });
        },

        /**
         * Generate unique client UUID v4
         */
        generateUUID() {
            if (typeof crypto !== 'undefined' && crypto.randomUUID) {
                return crypto.randomUUID();
            }
            return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function (c) {
                const r = Math.random() * 16 | 0;
                const v = c === 'x' ? r : (r & 0x3 | 0x8);
                return v.toString(16);
            });
        },

        /**
         * Get next sequential offline order number (e.g. OFF-TAK-260908-0001)
         */
        async getNextOfflineOrderNumber(orderType = 'TAKEAWAY') {
            const db = await this.openDB();
            const dateStr = new Date().toISOString().slice(2, 10).replace(/-/g, ''); // e.g. 260908
            const prefix = orderType === 'DINE_IN' ? 'DIN' : (orderType === 'DELIVERY' ? 'DEL' : 'TAK');
            const counterKey = `offline_${prefix}_${dateStr}`;

            return new Promise((resolve, reject) => {
                const tx = db.transaction('counters', 'readwrite');
                const store = tx.objectStore('counters');
                const getReq = store.get(counterKey);

                getReq.onsuccess = () => {
                    let nextCount = 1;
                    if (getReq.result && getReq.result.count) {
                        nextCount = getReq.result.count + 1;
                    }
                    store.put({ key: counterKey, count: nextCount });
                    const padded = String(nextCount).padStart(4, '0');
                    resolve(`OFF-${prefix}-${dateStr}-${padded}`);
                };

                getReq.onerror = (e) => reject(e.target.error);
            });
        },

        /**
         * Queue an offline order in IndexedDB
         */
        async queueOfflineOrder(orderData) {
            const db = await this.openDB();
            if (!orderData.client_uuid) {
                orderData.client_uuid = this.generateUUID();
            }
            if (!orderData.created_at) {
                orderData.created_at = new Date().toISOString();
            }
            orderData.status = 'pending';

            return new Promise((resolve, reject) => {
                const tx = db.transaction('offline_orders', 'readwrite');
                const store = tx.objectStore('offline_orders');
                const putReq = store.put(orderData);

                putReq.onsuccess = () => resolve(orderData);
                putReq.onerror = (e) => reject(e.target.error);
            });
        },

        /**
         * Get all pending unsynced offline orders
         */
        async getPendingOrders() {
            const db = await this.openDB();
            return new Promise((resolve, reject) => {
                const tx = db.transaction('offline_orders', 'readonly');
                const store = tx.objectStore('offline_orders');
                const index = store.index('status');
                const request = index.getAll('pending');

                request.onsuccess = () => resolve(request.result || []);
                request.onerror = (e) => reject(e.target.error);
            });
        },

        /**
         * Mark orders as synced after successful cloud sync
         */
        async markOrdersSynced(syncedList) {
            const db = await this.openDB();
            return new Promise((resolve, reject) => {
                const tx = db.transaction('offline_orders', 'readwrite');
                const store = tx.objectStore('offline_orders');

                syncedList.forEach(item => {
                    const uuid = item.client_uuid;
                    const getReq = store.get(uuid);
                    getReq.onsuccess = () => {
                        const order = getReq.result;
                        if (order) {
                            order.status = 'synced';
                            order.server_order_number = item.order_number;
                            order.synced_at = new Date().toISOString();
                            store.put(order);
                        }
                    };
                });

                tx.oncomplete = () => resolve(true);
                tx.onerror = (e) => reject(e.target.error);
            });
        },

        /**
         * Get count of pending unsynced orders
         */
        async getPendingCount() {
            const pending = await this.getPendingOrders();
            return pending.length;
        }
    };

    window.PosOfflineDB = PosOfflineDB;
})(window);
