/**
 * Food Point POS - Client-Side Offline Thermal Receipt & KOT Printer
 * Renders 80mm / 58mm receipts and KOTs via browser window.print() with Urdu support.
 */
(function (window) {
    'use strict';

    const PosOfflinePrinter = {
        /**
         * Print offline customer bill / receipt
         */
        printReceipt(order, settings = {}) {
            const restaurantName = settings.restaurant_name || 'Food Point';
            const restaurantAddress = settings.restaurant_address || '';
            const restaurantPhone = settings.restaurant_phone || '';
            const footerText = settings.receipt_footer || 'Thank you for dining with us!';
            const currency = settings.currency || 'Rs.';

            const dateStr = new Date(order.created_at || Date.now()).toLocaleString('en-PK', {
                day: '2-digit',
                month: 'short',
                year: 'numeric',
                hour: '2-digit',
                minute: '2-digit',
                hour12: true
            });

            let itemsHtml = '';
            (order.items || []).forEach(item => {
                const nameEn = item.name || item.product_name || 'Item';
                const nameUr = item.name_ur || item.product_name_ur || '';
                const qty = item.qty || item.quantity || 1;
                const price = parseFloat(item.price || item.unit_price || 0);
                const total = (qty * price).toFixed(2);

                itemsHtml += `
                    <tr>
                        <td style="padding: 4px 0; border-bottom: 1px dashed #ddd; vertical-align: top;">
                            <div style="font-weight: 600; font-size: 13px;">${nameEn}</div>
                            ${nameUr ? `<div style="font-size: 13px; font-family: 'Jameel Noori Nastaleeq', 'Noto Nastaliq Urdu', Arial, sans-serif; direction: rtl; text-align: right; color: #222;">${nameUr}</div>` : ''}
                            ${item.notes ? `<div style="font-size: 11px; font-style: italic; color: #666;">* ${item.notes}</div>` : ''}
                        </td>
                        <td style="padding: 4px 0; border-bottom: 1px dashed #ddd; text-align: center; vertical-align: top; font-size: 13px;">${qty}</td>
                        <td style="padding: 4px 0; border-bottom: 1px dashed #ddd; text-align: right; vertical-align: top; font-size: 13px;">${price.toFixed(2)}</td>
                        <td style="padding: 4px 0; border-bottom: 1px dashed #ddd; text-align: right; vertical-align: top; font-weight: 600; font-size: 13px;">${total}</td>
                    </tr>
                `;
            });

            const subtotal = parseFloat(order.subtotal || 0).toFixed(2);
            const discount = parseFloat(order.discount_amount || 0).toFixed(2);
            const tax = parseFloat(order.tax_amount || 0).toFixed(2);
            const delivery = parseFloat(order.delivery_charge || 0).toFixed(2);
            const grandTotal = parseFloat(order.grand_total || 0).toFixed(2);
            const tendered = parseFloat(order.tendered_amount || order.paid_amount || grandTotal).toFixed(2);
            const change = Math.max(0, (parseFloat(tendered) - parseFloat(grandTotal))).toFixed(2);

            const html = `
                <!DOCTYPE html>
                <html>
                <head>
                    <meta charset="utf-8">
                    <title>Offline Receipt - ${order.offline_order_number || 'POS'}</title>
                    <style>
                        @page {
                            margin: 0;
                            size: 80mm auto;
                        }
                        body {
                            margin: 0;
                            padding: 10px;
                            width: 78mm;
                            font-family: 'Courier New', Courier, monospace, Arial, sans-serif;
                            font-size: 12px;
                            color: #000;
                            line-height: 1.3;
                        }
                        .text-center { text-align: center; }
                        .text-end { text-align: right; }
                        .fw-bold { font-weight: bold; }
                        .border-top { border-top: 1px dashed #000; }
                        .border-bottom { border-bottom: 1px dashed #000; }
                        .offline-banner {
                            border: 2px dashed #000;
                            padding: 4px;
                            margin: 6px 0;
                            text-align: center;
                            font-size: 11px;
                            font-weight: bold;
                        }
                        table { width: 100%; border-collapse: collapse; }
                    </style>
                </head>
                <body>
                    <div class="text-center">
                        <div style="font-size: 18px; font-weight: bold; text-transform: uppercase;">${restaurantName}</div>
                        ${restaurantAddress ? `<div>${restaurantAddress}</div>` : ''}
                        ${restaurantPhone ? `<div>Tel: ${restaurantPhone}</div>` : ''}
                        <div class="offline-banner">
                            *** OFFLINE SALES RECEIPT ***<br>
                            <span style="font-size: 9px; font-weight: normal;">(Recorded Locally &bull; Cloud Sync Pending)</span>
                        </div>
                    </div>

                    <div style="margin: 8px 0; font-size: 12px;">
                        <div><strong>Order #:</strong> ${order.offline_order_number || 'OFFLINE'}</div>
                        <div><strong>Date:</strong> ${dateStr}</div>
                        <div><strong>Type:</strong> ${order.order_type || 'TAKEAWAY'} ${order.table_name ? `&bull; Table: ${order.table_name}` : ''}</div>
                        ${order.customer_name ? `<div><strong>Customer:</strong> ${order.customer_name}</div>` : ''}
                        ${order.customer_phone ? `<div><strong>Phone:</strong> ${order.customer_phone}</div>` : ''}
                    </div>

                    <table style="margin-top: 6px;">
                        <thead>
                            <tr class="border-top border-bottom">
                                <th style="text-align: left; padding: 4px 0;">Item</th>
                                <th style="text-align: center; padding: 4px 0; width: 35px;">Qty</th>
                                <th style="text-align: right; padding: 4px 0; width: 50px;">Price</th>
                                <th style="text-align: right; padding: 4px 0; width: 55px;">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${itemsHtml}
                        </tbody>
                    </table>

                    <table style="margin-top: 6px;" class="border-top">
                        <tr>
                            <td style="padding: 2px 0;">Subtotal:</td>
                            <td class="text-end" style="padding: 2px 0;">${currency} ${subtotal}</td>
                        </tr>
                        ${parseFloat(discount) > 0 ? `
                        <tr>
                            <td style="padding: 2px 0;">Discount:</td>
                            <td class="text-end" style="padding: 2px 0;">- ${currency} ${discount}</td>
                        </tr>` : ''}
                        ${parseFloat(tax) > 0 ? `
                        <tr>
                            <td style="padding: 2px 0;">Tax:</td>
                            <td class="text-end" style="padding: 2px 0;">+ ${currency} ${tax}</td>
                        </tr>` : ''}
                        ${parseFloat(delivery) > 0 ? `
                        <tr>
                            <td style="padding: 2px 0;">Delivery Fee:</td>
                            <td class="text-end" style="padding: 2px 0;">+ ${currency} ${delivery}</td>
                        </tr>` : ''}
                        <tr style="font-size: 15px; font-weight: bold;" class="border-top">
                            <td style="padding: 4px 0;">GRAND TOTAL:</td>
                            <td class="text-end" style="padding: 4px 0;">${currency} ${grandTotal}</td>
                        </tr>
                        ${order.is_finalized || order.paid_amount > 0 ? `
                        <tr>
                            <td style="padding: 2px 0;">Cash Tendered:</td>
                            <td class="text-end" style="padding: 2px 0;">${currency} ${tendered}</td>
                        </tr>
                        <tr>
                            <td style="padding: 2px 0;">Change Due:</td>
                            <td class="text-end" style="padding: 2px 0;">${currency} ${change}</td>
                        </tr>` : ''}
                    </table>

                    <div class="text-center border-top" style="margin-top: 12px; padding-top: 8px; font-size: 11px;">
                        <div>${footerText}</div>
                        <div style="margin-top: 4px; font-size: 9px; color: #555;">Food Point POS &bull; EasyAdmin Pro</div>
                    </div>
                </body>
                </html>
            `;

            this._executePrint(html);
        },

        /**
         * Print offline Kitchen Order Ticket (KOT) with Urdu names
         */
        printKOT(order, kotNumber = 1) {
            const dateStr = new Date(order.created_at || Date.now()).toLocaleTimeString('en-PK', {
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit',
                hour12: true
            });

            let itemsHtml = '';
            (order.items || []).forEach(item => {
                const nameEn = item.name || item.product_name || 'Item';
                const nameUr = item.name_ur || item.product_name_ur || '';
                const qty = item.qty || item.quantity || 1;

                itemsHtml += `
                    <div style="padding: 6px 0; border-bottom: 1px dashed #000;">
                        <div style="display: flex; justify-content: space-between; align-items: baseline;">
                            <span style="font-size: 16px; font-weight: bold; min-width: 35px;">[ ${qty}x ]</span>
                            <span style="font-size: 16px; font-weight: bold; flex-grow: 1; padding-left: 6px;">${nameEn}</span>
                        </div>
                        ${nameUr ? `
                            <div style="font-size: 16px; font-weight: bold; font-family: 'Jameel Noori Nastaleeq', 'Noto Nastaliq Urdu', Arial, sans-serif; direction: rtl; text-align: right; padding-top: 2px;">
                                ${nameUr}
                            </div>
                        ` : ''}
                        ${item.notes ? `<div style="font-size: 12px; font-style: italic; color: #333; margin-top: 2px;">NOTE: ${item.notes}</div>` : ''}
                    </div>
                `;
            });

            const html = `
                <!DOCTYPE html>
                <html>
                <head>
                    <meta charset="utf-8">
                    <title>KOT - ${order.offline_order_number || 'KITCHEN'}</title>
                    <style>
                        @page {
                            margin: 0;
                            size: 80mm auto;
                        }
                        body {
                            margin: 0;
                            padding: 10px;
                            width: 78mm;
                            font-family: Arial, Helvetica, sans-serif;
                            color: #000;
                            line-height: 1.3;
                        }
                        .text-center { text-align: center; }
                    </style>
                </head>
                <body>
                    <div class="text-center" style="border-bottom: 2px solid #000; padding-bottom: 6px;">
                        <div style="font-size: 20px; font-weight: 900; letter-spacing: 1px;">KITCHEN ORDER (KOT)</div>
                        <div style="font-size: 12px; font-weight: bold; background: #000; color: #fff; display: inline-block; padding: 2px 8px; margin-top: 4px;">OFFLINE TICKET #${kotNumber}</div>
                    </div>

                    <div style="margin: 8px 0; font-size: 13px; font-weight: bold; border-bottom: 1px solid #000; padding-bottom: 6px;">
                        <div style="font-size: 15px;">Order #: ${order.offline_order_number || 'OFFLINE'}</div>
                        <div>Time: ${dateStr}</div>
                        <div style="font-size: 16px; margin-top: 2px;">
                            Type: <u>${order.order_type || 'TAKEAWAY'}</u> 
                            ${order.table_name ? `&bull; TABLE: <span style="font-size: 18px; font-weight: 900;">${order.table_name}</span>` : ''}
                        </div>
                        ${order.order_notes ? `<div style="margin-top: 4px; color: #d00;">Special Request: ${order.order_notes}</div>` : ''}
                    </div>

                    <div style="margin-top: 8px;">
                        ${itemsHtml}
                    </div>

                    <div class="text-center" style="margin-top: 14px; font-size: 11px; border-top: 1px solid #000; padding-top: 4px;">
                        *** SEND TO KITCHEN CHEF ***
                    </div>
                </body>
                </html>
            `;

            this._executePrint(html);
        },

        /**
         * Private helper to execute print via hidden iframe
         */
        _executePrint(htmlContent) {
            let iframe = document.getElementById('posOfflinePrintFrame');
            if (!iframe) {
                iframe = document.createElement('iframe');
                iframe.id = 'posOfflinePrintFrame';
                iframe.style.position = 'fixed';
                iframe.style.right = '0';
                iframe.style.bottom = '0';
                iframe.style.width = '0';
                iframe.style.height = '0';
                iframe.style.border = '0';
                document.body.appendChild(iframe);
            }

            const doc = iframe.contentWindow.document;
            doc.open();
            doc.write(htmlContent);
            doc.close();

            setTimeout(() => {
                try {
                    iframe.contentWindow.focus();
                    iframe.contentWindow.print();
                } catch (e) {
                    console.error('Print execution failed:', e);
                }
            }, 250);
        }
    };

    window.PosOfflinePrinter = PosOfflinePrinter;
})(window);
