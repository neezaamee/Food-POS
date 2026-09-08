# Food Point - Modern Restaurant Point of Sale (POS)

A modern, full-featured Restaurant Point of Sale (POS) and Management System built with **Laravel 12**, **Livewire 3**, **Alpine.js**, and **Bootstrap 5**.

Designed for high-speed counter operations, kitchen coordination, inventory control, and seamless offline resilience during internet outages.

---

## 🌟 Key Features

### 1. High-Speed Live POS Counter
- **Multi-Channel Ordering**: Fast toggle between **Takeaway**, **Dine-In**, and **Delivery**.
- **Mandatory Customer Validation**: Enforces customer phone (11-digit format starting with `03`) and name before order placement.
- **Mandatory Table Selection**: Dine-In orders strictly enforce table assignment across restaurant sections before saving.
- **Fast Keyboard & Barcode Punching**: Rapid item punching via keyboard shortcuts, item code input (e.g. `BUR-01`, `2*BUR-01`), and barcode scanner support.
- **Customizable Order Notes & Modifiers**: Per-item kitchen cooking instructions.

### 2. ⚡ Offline Order Placement & Auto-Sync
- **100% Offline Resilience**: Continues taking orders seamlessly when internet or Wi-Fi connectivity drops.
- **Client-Side Storage**: Uses browser **IndexedDB** (`catalog`, `offline_orders`, `counters`) and Service Worker cache.
- **Independent Sequence Numbering**: Generates unique daily offline order numbers (`OFF-TAK-YYMMDD-XXXX`, `OFF-DIN-YYMMDD-XXXX`, `OFF-DEL-YYMMDD-XXXX`).
- **Offline Checkout Modal**: Complete offline cash or card transactions with quick tender buttons and automated change calculation.
- **Idempotent Background Synchronization**: Automatically uploads queued offline orders to the cloud database the moment connection returns with zero duplication risk.
- **Live Network Status Indicator**: Real-time badge in top navigation (`🟢 Online`, `🔴 Offline Mode`, `🟡 Syncing...`).

### 3. 🖨️ Thermal Printing & Urdu Support
- **80mm Thermal Receipt Printing**: Uses native browser `window.print()` targeting default receipt printers without external drivers.
- **Urdu Script in KOT & Receipts**: Full support for Urdu product names (`name_ur`) rendered in clean Nastaliq font for kitchen staff and customers.
- **Incremental Kitchen Order Tickets (KOT)**: When recalling and adding items to an open running order, generates new sequential KOT tickets (`KOT #1`, `KOT #2`, etc.) containing only newly added delta quantities.
- **KOT Lifecycle Tracking**: Tracks kitchen prep stages (`Prep`, `Marination`, `Baking`, `Packing`, `Ready`).

### 4. 🍽️ Table & Dining Section Management
- Visual floor plan divided by dining sections (e.g., Main Hall, Family Hall, Rooftop).
- Live occupancy indicators (Available, Occupied, Reserved).
- Easy table-to-table order transfer functionality.

### 5. 📦 Inventory, Recipes & Supplier Purchases
- **Dual Product Types**: Standard Menu Products vs. Raw Material Ingredients.
- **Product Variations**: Multiple sizes/flavors (e.g. Small, Medium, Large) with individual costs and prices.
- **Recipe Management**: Automatic stock deduction of raw materials (dough, cheese, meat) upon order finalization.
- **Purchases CRUD**: Complete purchasing workflow with suppliers, units of measure, and purchase history.

### 6. 💰 Cash Control & Shift Management
- **Shift Enforcement**: Orders cannot be punched without an active open cash shift.
- **Opening Float & End-of-Shift Cash Balancing**: Cashiers log float upon opening and count totals upon shift closure.
- **Day Close**: Comprehensive end-of-day reconciliation aggregating multiple shift sales.
- **Role-Gated Cancellations & Refunds**: Controlled permissions for manager/owner order cancellations and sale returns before shift closure.

### 7. 📊 Reports & Rider Tracking
- Daily Sales and Performance Summaries.
- Delivery Rider logs with estimated distance (KM) and delivery charges breakdown.
- Financial audit trails and item sales reports.

---

## 🛠️ Technology Stack

- **Backend Framework**: Laravel 12 (PHP 8.2+)
- **Reactive UI**: Livewire 3 & Alpine.js
- **Frontend Styling**: Bootstrap 5, Bootstrap Icons, Phosphor Icons
- **Offline Storage**: IndexedDB API & Progressive Web App (PWA) Service Worker
- **Database**: MySQL / SQLite
- **Code Formatter & Quality**: Laravel Pint & PHPUnit

---

## 🚀 Installation & Local Setup

### Prerequisites
- PHP >= 8.2 with `pdo`, `mbstring`, `openssl`, `curl`, `sqlite3` or `mysql` extensions
- Composer >= 2.x
- Node.js >= 18.x & NPM
- Git

### Quickstart

1. **Clone the repository:**
   ```bash
   git clone https://github.com/neezaamee/Food-POS.git
   cd Food-POS
   ```

2. **Install PHP Dependencies:**
   ```bash
   composer install
   ```

3. **Install Frontend Dependencies:**
   ```bash
   npm install
   ```

4. **Environment Setup:**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

5. **Run Migrations & Seed Sample Data:**
   ```bash
   php artisan migrate --seed
   ```

6. **Build Frontend Assets:**
   ```bash
   npm run build
   ```

7. **Start the Local Development Server:**
   ```bash
   php artisan serve
   ```
   Open `http://localhost:8000` in your browser.

---

## 🧪 Testing

Run automated feature and unit tests with PHPUnit:
```bash
php artisan test
```

To run specific test suites:
```bash
php artisan test --filter=PosOfflineSyncTest
php artisan test --filter=KotIncrementalAndUrduSupportTest
```

---

## 📄 License
This project is open-sourced software licensed under the [MIT license](LICENSE).
