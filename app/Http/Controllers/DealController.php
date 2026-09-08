<?php

namespace App\Http\Controllers;

use App\Models\Deal;
use App\Models\DealItem;
use App\Models\Product;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DealController extends Controller
{
    public function index(Request $request)
    {
        $query = Deal::with(['items.product', 'product'])->latest();

        if ($request->filled('search')) {
            $term = '%'.$request->search.'%';
            $query->where(function ($q) use ($term) {
                $q->where('name', 'like', $term)
                    ->orWhere('code', 'like', $term)
                    ->orWhere('description', 'like', $term);
            });
        }

        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'active');
        }

        $deals = $query->paginate(12)->withQueryString();

        // Products to select when building a deal
        $availableProducts = Product::where('is_active', true)
            ->where(function ($q) {
                $q->whereDoesntHave('category', function ($catQuery) {
                    $catQuery->where('slug', 'packages-deals');
                })->orWhereNull('category_id');
            })
            ->orderBy('name')
            ->get(['id', 'name', 'code', 'sale_price', 'cost_price']);

        $totalDeals = Deal::count();
        $activeDeals = Deal::where('is_active', true)->count();

        return view('resources.deals.index', compact('deals', 'availableProducts', 'totalDeals', 'activeDeals'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:150',
            'code' => 'required|string|max:50|unique:deals,code',
            'sale_price' => 'required|numeric|min:0',
            'cost_price' => 'nullable|numeric|min:0',
            'description' => 'nullable|string|max:1000',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|numeric|min:0.01',
        ]);

        try {
            DB::transaction(function () use ($request) {
                $deal = Deal::create([
                    'name' => $request->name,
                    'code' => strtoupper($request->code),
                    'sale_price' => $request->sale_price,
                    'cost_price' => $request->cost_price ?: 0.00,
                    'description' => $request->description,
                    'is_active' => $request->boolean('is_active', true),
                ]);

                $calcCost = 0.00;
                foreach ($request->items as $itemData) {
                    $product = Product::find($itemData['product_id']);
                    if ($product) {
                        $qty = (float) $itemData['quantity'];
                        $calcCost += ((float) $product->cost_price * $qty);

                        DealItem::create([
                            'deal_id' => $deal->id,
                            'product_id' => $product->id,
                            'quantity' => $qty,
                            'unit_price' => $product->sale_price,
                        ]);
                    }
                }

                if (empty($deal->cost_price)) {
                    $deal->cost_price = $calcCost;
                    $deal->save();
                }

                // Sync deal to POS products catalog under "Packages & Deals"
                $deal->syncProduct();
            });

            return redirect()->route('resources.deals.index')->with('success', "Package / Deal '{$request->name}' created and added to POS catalog successfully!");
        } catch (Exception $e) {
            return back()->withInput()->with('error', 'Error creating deal: '.$e->getMessage());
        }
    }

    public function update(Request $request, int $id)
    {
        $deal = Deal::findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:150',
            'code' => "required|string|max:50|unique:deals,code,{$deal->id}",
            'sale_price' => 'required|numeric|min:0',
            'cost_price' => 'nullable|numeric|min:0',
            'description' => 'nullable|string|max:1000',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|numeric|min:0.01',
        ]);

        try {
            DB::transaction(function () use ($request, $deal) {
                $deal->update([
                    'name' => $request->name,
                    'code' => strtoupper($request->code),
                    'sale_price' => $request->sale_price,
                    'cost_price' => $request->cost_price ?: 0.00,
                    'description' => $request->description,
                    'is_active' => $request->boolean('is_active', true),
                ]);

                // Re-sync components
                $deal->items()->delete();
                $calcCost = 0.00;

                foreach ($request->items as $itemData) {
                    $product = Product::find($itemData['product_id']);
                    if ($product) {
                        $qty = (float) $itemData['quantity'];
                        $calcCost += ((float) $product->cost_price * $qty);

                        DealItem::create([
                            'deal_id' => $deal->id,
                            'product_id' => $product->id,
                            'quantity' => $qty,
                            'unit_price' => $product->sale_price,
                        ]);
                    }
                }

                if (empty($deal->cost_price)) {
                    $deal->cost_price = $calcCost;
                    $deal->save();
                }

                // Sync deal to POS catalog
                $deal->syncProduct();
            });

            return redirect()->route('resources.deals.index')->with('success', "Deal '{$deal->name}' updated successfully!");
        } catch (Exception $e) {
            return back()->withInput()->with('error', 'Error updating deal: '.$e->getMessage());
        }
    }

    public function destroy(int $id)
    {
        $deal = Deal::findOrFail($id);
        $dealName = $deal->name;

        DB::transaction(function () use ($deal) {
            if ($deal->product) {
                $deal->product->delete();
            }
            $deal->delete();
        });

        return redirect()->route('resources.deals.index')->with('success', "Deal '{$dealName}' and its POS listing deleted successfully.");
    }

    public function toggleStatus(int $id)
    {
        $deal = Deal::findOrFail($id);
        $deal->is_active = ! $deal->is_active;
        $deal->save();

        if ($deal->product) {
            $deal->product->is_active = $deal->is_active;
            $deal->product->save();
        }

        $statusLabel = $deal->is_active ? 'activated' : 'deactivated';

        return back()->with('success', "Deal '{$deal->name}' {$statusLabel} successfully.");
    }
}
