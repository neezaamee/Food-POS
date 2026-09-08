<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\StockAdjustment;
use App\Models\StockMovement;
use App\Models\Unit;
use App\Services\Inventory\StockService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InventoryController extends Controller
{
    public function overview()
    {
        $products = Product::with(['category', 'unit', 'recipeItems'])->get();
        $totalStockValue = $products->sum(function ($p) {
            return (float) $p->current_stock * (float) $p->cost_price;
        });
        $lowStockCount = $products->filter(fn ($p) => $p->isLowStock())->count();

        return view('inventory.overview', compact('products', 'totalStockValue', 'lowStockCount'));
    }

    public function ledger(Request $request)
    {
        $query = StockMovement::with(['product.unit', 'user'])->latest();

        if ($request->filled('product_id')) {
            $query->where('product_id', $request->product_id);
        }

        if ($request->filled('movement_type')) {
            $query->where('movement_type', $request->movement_type);
        }

        $movements = $query->paginate(20)->withQueryString();
        $products = Product::orderBy('name')->get();

        return view('inventory.ledger', compact('movements', 'products'));
    }

    public function adjustments()
    {
        $adjustments = StockAdjustment::with(['product', 'user'])->latest()->paginate(15);
        $products = Product::orderBy('name')->get();

        return view('inventory.adjustments', compact('adjustments', 'products'));
    }

    public function storeAdjustment(Request $request, StockService $stockService)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'type' => 'required|in:addition,subtraction',
            'quantity' => 'required|numeric|min:0.01',
            'reason' => 'required|string|max:255',
        ]);

        try {
            $adj = $stockService->adjustStock(
                $request->product_id,
                $request->type,
                $request->quantity,
                $request->reason
            );

            return back()->with('success', "Stock Adjustment #{$adj->adjustment_number} posted successfully!");
        } catch (Exception $e) {
            return back()->with('error', 'Failed to post adjustment: '.$e->getMessage());
        }
    }

    public function purchases(Request $request)
    {
        $query = Purchase::with(['items.product.category', 'items.product.unit', 'user'])->latest();

        if ($request->filled('search')) {
            $term = '%'.$request->search.'%';
            $query->where(function ($q) use ($term) {
                $q->where('purchase_number', 'like', $term)
                    ->orWhere('supplier_name', 'like', $term)
                    ->orWhere('invoice_number', 'like', $term)
                    ->orWhereHas('items.product', function ($pq) use ($term) {
                        $pq->where('name', 'like', $term)
                            ->orWhere('code', 'like', $term);
                    });
            });
        }

        if ($request->filled('date_from')) {
            $query->whereDate('purchase_date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('purchase_date', '<=', $request->date_to);
        }

        if ($request->filled('category_id')) {
            $catId = $request->category_id;
            $query->whereHas('items.product', function ($pq) use ($catId) {
                $pq->where('category_id', $catId);
            });
        }

        $purchases = $query->paginate(15)->withQueryString();

        // Purchasable Products (All active items: Menu items, Raw materials, and Retail goods) with Category and Unit
        $purchasableProducts = Product::where('is_active', true)
            ->with(['category', 'unit'])
            ->orderBy('name')
            ->get();

        $categories = Category::where('is_active', true)->orderBy('name')->get();
        $units = Unit::orderBy('name')->get();

        // Procurement KPI stats
        $totalPurchasesValue = (float) Purchase::sum('grand_total');
        $totalPurchasesCount = Purchase::count();
        $uniqueSuppliersCount = Purchase::distinct('supplier_name')->count('supplier_name');

        return view('inventory.purchases', compact(
            'purchases',
            'purchasableProducts',
            'categories',
            'units',
            'totalPurchasesValue',
            'totalPurchasesCount',
            'uniqueSuppliersCount'
        ));
    }

    public function storePurchase(Request $request)
    {
        // Support both multi-item structure: items: [ [product_id, quantity, unit_cost], ... ]
        // and legacy single-item structure: product_id, quantity, unit_cost
        $request->validate([
            'supplier_name' => 'required|string|max:150',
            'invoice_number' => 'nullable|string|max:100',
            'purchase_date' => 'required|date',
            'payment_method' => 'nullable|string|max:30',
            'notes' => 'nullable|string|max:500',
            'items' => 'nullable|array',
            'items.*.product_id' => 'required_with:items|exists:products,id',
            'items.*.quantity' => 'required_with:items|numeric|min:0.001',
            'items.*.unit_cost' => 'nullable|numeric|min:0',
            'items.*.cost_price' => 'nullable|numeric|min:0',
            'product_id' => 'required_without:items|exists:products,id',
            'quantity' => 'required_without:items|numeric|min:0.001',
            'unit_cost' => 'nullable|numeric|min:0',
            'cost_price' => 'nullable|numeric|min:0',
        ]);

        // Prepare items array
        $itemsData = [];
        if ($request->filled('items') && is_array($request->items)) {
            foreach ($request->items as $item) {
                $itemsData[] = [
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'unit_cost' => $item['unit_cost'] ?? $item['cost_price'] ?? 0,
                ];
            }
        } elseif ($request->filled('product_id')) {
            $itemsData[] = [
                'product_id' => $request->product_id,
                'quantity' => $request->quantity,
                'unit_cost' => $request->input('unit_cost', $request->input('cost_price', 0)),
            ];
        }

        if (empty($itemsData)) {
            return back()->with('error', 'Please add at least one product line item to this purchase.');
        }

        return DB::transaction(function () use ($request, $itemsData) {
            $year = date('Y');
            $count = Purchase::whereYear('created_at', $year)->count() + 1;
            do {
                $purchaseNumber = sprintf('PO-%s%05d', $year, $count);
                $count++;
            } while (Purchase::where('purchase_number', $purchaseNumber)->exists());

            $subtotal = 0.00;
            foreach ($itemsData as $item) {
                $subtotal += ((float) $item['quantity'] * (float) $item['unit_cost']);
            }

            $purchase = Purchase::create([
                'purchase_number' => $purchaseNumber,
                'supplier_name' => $request->supplier_name,
                'invoice_number' => $request->invoice_number,
                'purchase_date' => $request->purchase_date,
                'subtotal' => $subtotal,
                'tax_amount' => 0.00,
                'grand_total' => $subtotal,
                'paid_amount' => $subtotal,
                'payment_method' => $request->input('payment_method', 'cash'),
                'status' => 'received',
                'notes' => $request->notes,
                'user_id' => auth()->id(),
            ]);

            foreach ($itemsData as $item) {
                $product = Product::lockForUpdate()->findOrFail($item['product_id']);
                $qty = (float) $item['quantity'];
                $cost = (float) $item['unit_cost'];
                $lineSubtotal = $qty * $cost;

                $purchase->items()->create([
                    'product_id' => $product->id,
                    'quantity' => $qty,
                    'unit_cost' => $cost,
                    'subtotal' => $lineSubtotal,
                ]);

                // Update stock and cost price
                $newBalance = (float) $product->current_stock + $qty;
                $product->current_stock = $newBalance;
                if ($cost > 0) {
                    $product->cost_price = $cost;
                }
                $product->save();

                StockMovement::create([
                    'product_id' => $product->id,
                    'movement_type' => 'purchase',
                    'quantity' => $qty,
                    'unit_cost' => $cost,
                    'reference_type' => 'Purchase',
                    'reference_id' => $purchase->id,
                    'balance_after' => $newBalance,
                    'notes' => "Purchase #{$purchase->purchase_number} from {$request->supplier_name}",
                    'user_id' => auth()->id(),
                ]);
            }

            return redirect()->route('inventory.purchases')
                ->with('success', "Purchase #{$purchase->purchase_number} for Rs. ".number_format($purchase->grand_total, 2).' recorded and stock updated!');
        });
    }

    public function showPurchase(int $id)
    {
        $purchase = Purchase::with(['items.product.category', 'items.product.unit', 'user'])->findOrFail($id);

        return response()->json([
            'id' => $purchase->id,
            'purchase_number' => $purchase->purchase_number,
            'supplier_name' => $purchase->supplier_name,
            'invoice_number' => $purchase->invoice_number,
            'purchase_date' => $purchase->purchase_date->format('Y-m-d'),
            'purchase_date_formatted' => $purchase->purchase_date->format('d M Y'),
            'subtotal' => (float) $purchase->subtotal,
            'tax_amount' => (float) $purchase->tax_amount,
            'grand_total' => (float) $purchase->grand_total,
            'paid_amount' => (float) $purchase->paid_amount,
            'payment_method' => strtolower($purchase->payment_method ?? 'cash'),
            'payment_method_formatted' => ucfirst($purchase->payment_method ?? 'cash'),
            'status' => $purchase->status,
            'notes' => $purchase->notes,
            'recorded_by' => $purchase->user?->name ?? 'Staff',
            'created_at' => $purchase->created_at->format('d M Y, h:i A'),
            'items' => $purchase->items->map(function ($item) {
                return [
                    'id' => $item->id,
                    'product_id' => $item->product_id,
                    'product_name' => $item->product?->name ?? 'Unknown',
                    'product_code' => $item->product?->code ?? '-',
                    'category_name' => $item->product?->category?->name ?? 'General',
                    'unit_code' => $item->product?->unit?->code ?? $item->product?->unit?->name ?? 'Units',
                    'quantity' => (float) $item->quantity,
                    'unit_cost' => (float) $item->unit_cost,
                    'subtotal' => (float) $item->subtotal,
                ];
            }),
        ]);
    }

    public function editPurchaseData(int $id)
    {
        $purchase = Purchase::with(['items.product.category', 'items.product.unit'])->findOrFail($id);

        return response()->json([
            'id' => $purchase->id,
            'purchase_number' => $purchase->purchase_number,
            'supplier_name' => $purchase->supplier_name,
            'invoice_number' => $purchase->invoice_number,
            'purchase_date' => $purchase->purchase_date->format('Y-m-d'),
            'payment_method' => $purchase->payment_method ?? 'cash',
            'notes' => $purchase->notes,
            'items' => $purchase->items->map(function ($item) {
                return [
                    'id' => $item->id,
                    'product_id' => $item->product_id,
                    'product_name' => $item->product?->name,
                    'category_name' => $item->product?->category?->name ?? 'General',
                    'unit_code' => $item->product?->unit?->code ?? $item->product?->unit?->name ?? 'Units',
                    'quantity' => (float) $item->quantity,
                    'unit_cost' => (float) $item->unit_cost,
                    'subtotal' => (float) $item->subtotal,
                ];
            }),
        ]);
    }

    public function updatePurchase(Request $request, int $id)
    {
        $purchase = Purchase::with('items')->findOrFail($id);

        $request->validate([
            'supplier_name' => 'required|string|max:150',
            'invoice_number' => 'nullable|string|max:100',
            'purchase_date' => 'required|date',
            'payment_method' => 'nullable|string|max:30',
            'notes' => 'nullable|string|max:500',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|numeric|min:0.001',
            'items.*.unit_cost' => 'nullable|numeric|min:0',
            'items.*.cost_price' => 'nullable|numeric|min:0',
        ]);

        return DB::transaction(function () use ($purchase, $request) {
            // Revert old quantities from stock first
            foreach ($purchase->items as $oldItem) {
                $p = Product::lockForUpdate()->find($oldItem->product_id);
                if ($p) {
                    $p->current_stock = (float) $p->current_stock - (float) $oldItem->quantity;
                    $p->save();
                }
            }

            // Delete old purchase items
            $purchase->items()->delete();

            // Insert new items and apply new stock
            $subtotal = 0.00;
            foreach ($request->items as $item) {
                $product = Product::lockForUpdate()->findOrFail($item['product_id']);
                $qty = (float) $item['quantity'];
                $cost = (float) ($item['unit_cost'] ?? $item['cost_price'] ?? 0);
                $lineTotal = $qty * $cost;
                $subtotal += $lineTotal;

                $purchase->items()->create([
                    'product_id' => $product->id,
                    'quantity' => $qty,
                    'unit_cost' => $cost,
                    'subtotal' => $lineTotal,
                ]);

                // Apply new stock
                $newBalance = (float) $product->current_stock + $qty;
                $product->current_stock = $newBalance;
                if ($cost > 0) {
                    $product->cost_price = $cost;
                }
                $product->save();

                StockMovement::create([
                    'product_id' => $product->id,
                    'movement_type' => 'adjustment',
                    'quantity' => $qty,
                    'unit_cost' => $cost,
                    'reference_type' => 'Purchase',
                    'reference_id' => $purchase->id,
                    'balance_after' => $newBalance,
                    'notes' => "Updated Purchase #{$purchase->purchase_number}",
                    'user_id' => auth()->id(),
                ]);
            }

            $purchase->update([
                'supplier_name' => $request->supplier_name,
                'invoice_number' => $request->invoice_number,
                'purchase_date' => $request->purchase_date,
                'subtotal' => $subtotal,
                'grand_total' => $subtotal,
                'paid_amount' => $subtotal,
                'payment_method' => $request->input('payment_method', 'cash'),
                'notes' => $request->notes,
            ]);

            return redirect()->route('inventory.purchases')
                ->with('success', "Purchase #{$purchase->purchase_number} updated and inventory reconciled successfully!");
        });
    }

    public function destroyPurchase(int $id)
    {
        $purchase = Purchase::with('items')->findOrFail($id);

        return DB::transaction(function () use ($purchase) {
            foreach ($purchase->items as $item) {
                $product = Product::lockForUpdate()->find($item->product_id);
                if ($product) {
                    $newBalance = (float) $product->current_stock - (float) $item->quantity;
                    $product->current_stock = $newBalance;
                    $product->save();

                    StockMovement::create([
                        'product_id' => $product->id,
                        'movement_type' => 'adjustment',
                        'quantity' => -(float) $item->quantity,
                        'unit_cost' => (float) $item->unit_cost,
                        'reference_type' => 'Purchase',
                        'reference_id' => $purchase->id,
                        'balance_after' => $newBalance,
                        'notes' => "Cancelled / Deleted Purchase #{$purchase->purchase_number}",
                        'user_id' => auth()->id(),
                    ]);
                }
            }

            $poNumber = $purchase->purchase_number;
            $purchase->items()->delete();
            $purchase->delete();

            return redirect()->route('inventory.purchases')
                ->with('success', "Purchase #{$poNumber} deleted and received stock reversed from inventory.");
        });
    }

    public function storePurchaseProduct(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:150',
            'name_ur' => 'nullable|string|max:150',
            'name_urdu' => 'nullable|string|max:150',
            'type' => 'nullable|in:raw_material,standard',
            'category_id' => 'required|exists:categories,id',
            'unit_id' => 'required|exists:units,id',
            'cost_price' => 'nullable|numeric|min:0',
            'opening_stock' => 'nullable|numeric|min:0',
            'min_stock' => 'nullable|numeric|min:0',
            'min_stock_alert' => 'nullable|numeric|min:0',
        ]);

        return DB::transaction(function () use ($request) {
            $type = $request->input('type', 'raw_material');
            $nameUrdu = $request->input('name_urdu', $request->input('name_ur'));
            $minStock = $request->input('min_stock_alert', $request->input('min_stock', 5));
            $prefix = $type === 'raw_material' ? 'RAW' : 'RET';
            $cleaned = strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $request->name), 0, 4)) ?: 'ITEM';
            $code = $prefix.'-'.$cleaned.'-'.rand(100, 999);
            while (Product::where('code', $code)->exists()) {
                $code = $prefix.'-'.$cleaned.'-'.rand(1000, 9999);
            }

            $cost = (float) ($request->cost_price ?? 0);
            $openingStock = (float) ($request->opening_stock ?? 0);

            $product = Product::create([
                'name' => $request->name,
                'name_ur' => $nameUrdu,
                'type' => $type,
                'code' => $code,
                'sku' => $code,
                'barcode' => rand(100000, 999999),
                'category_id' => $request->category_id,
                'unit_id' => $request->unit_id,
                'cost_price' => $cost,
                'selling_price' => 0.00,
                'current_stock' => $openingStock,
                'min_stock_alert' => $minStock,
                'is_active' => true,
            ]);

            if ($openingStock > 0) {
                StockMovement::create([
                    'product_id' => $product->id,
                    'movement_type' => 'opening',
                    'quantity' => $openingStock,
                    'unit_cost' => $cost,
                    'balance_after' => $openingStock,
                    'notes' => 'Opening Stock on Creation',
                    'user_id' => auth()->id(),
                ]);
            }

            if ($request->wantsJson() || $request->ajax()) {
                $product->load(['category', 'unit']);

                return response()->json([
                    'success' => true,
                    'product' => [
                        'id' => $product->id,
                        'name' => $product->name,
                        'type' => $product->type,
                        'code' => $product->code,
                        'category_id' => $product->category_id,
                        'category_name' => $product->category?->name ?? 'General',
                        'unit_id' => $product->unit_id,
                        'unit_code' => $product->unit?->code ?? $product->unit?->name ?? 'Units',
                        'cost_price' => (float) $product->cost_price,
                        'current_stock' => (float) $product->current_stock,
                    ],
                    'message' => "Item '{$product->name}' created successfully!",
                ]);
            }

            return back()->with('success', "Item '{$product->name}' created successfully!");
        });
    }
}
