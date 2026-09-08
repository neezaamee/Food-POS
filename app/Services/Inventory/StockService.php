<?php

namespace App\Services\Inventory;

use App\Models\Deal;
use App\Models\Order;
use App\Models\Product;
use App\Models\SaleReturn;
use App\Models\StockAdjustment;
use App\Models\StockMovement;
use Illuminate\Support\Facades\DB;

class StockService
{
    /**
     * Deduct stock for a finalized order
     */
    public function deductOrderStock(Order $order): void
    {
        DB::transaction(function () use ($order) {
            foreach ($order->items as $item) {
                $product = Product::lockForUpdate()->findOrFail($item->product_id);
                $qtyToDeduct = (float) $item->quantity;

                // Check if this product is a Deal/Package bundle
                $deal = Deal::where('product_id', $product->id)->with('items.product')->first();

                if ($deal && $deal->items->isNotEmpty()) {
                    // Deduct stock for each individual constituent item in the package
                    foreach ($deal->items as $dealItem) {
                        $componentProduct = Product::lockForUpdate()->find($dealItem->product_id);
                        if ($componentProduct) {
                            $componentQtyToDeduct = (float) $dealItem->quantity * $qtyToDeduct;
                            $this->deductProductStockOrRecipe(
                                $componentProduct,
                                $componentQtyToDeduct,
                                $order,
                                "Deal package ('{$deal->name}')"
                            );
                        }
                    }
                } else {
                    $this->deductProductStockOrRecipe($product, $qtyToDeduct, $order);
                }
            }
        });
    }

    /**
     * Deduct stock for a single product or its constituent recipe ingredients
     */
    public function deductProductStockOrRecipe(Product $product, float $quantity, Order $order, string $context = ''): void
    {
        $product->loadMissing('recipeItems.ingredient');

        if ($product->recipeItems->isNotEmpty()) {
            // Deduct each raw ingredient based on recipe quantity * ordered quantity
            foreach ($product->recipeItems as $recipeItem) {
                $ingredient = Product::lockForUpdate()->find($recipeItem->ingredient_id);
                if ($ingredient) {
                    $ingredientQtyToDeduct = (float) $recipeItem->quantity * $quantity;
                    $newBalance = (float) $ingredient->current_stock - $ingredientQtyToDeduct;
                    $ingredient->current_stock = $newBalance;
                    $ingredient->save();

                    $note = $context
                        ? "{$context} -> Recipe ingredient ('{$ingredient->name}') for '{$product->name}'"
                        : "Recipe ingredient deduction ('{$ingredient->name}') for '{$product->name}' (Order #{$order->order_number})";

                    StockMovement::create([
                        'product_id' => $ingredient->id,
                        'movement_type' => 'sale',
                        'quantity' => -$ingredientQtyToDeduct,
                        'unit_cost' => $ingredient->cost_price,
                        'reference_type' => 'Order',
                        'reference_id' => $order->id,
                        'balance_after' => $newBalance,
                        'notes' => $note,
                        'user_id' => $order->user_id,
                    ]);
                }
            }
        } else {
            // Standard / retail item with no recipe: deduct product itself
            $newBalance = (float) $product->current_stock - $quantity;
            $product->current_stock = $newBalance;
            $product->save();

            $note = $context ? "{$context} -> '{$product->name}'" : "Sale deduction for Order #{$order->order_number} ({$order->order_type})";

            StockMovement::create([
                'product_id' => $product->id,
                'movement_type' => 'sale',
                'quantity' => -$quantity,
                'unit_cost' => $product->cost_price,
                'reference_type' => 'Order',
                'reference_id' => $order->id,
                'balance_after' => $newBalance,
                'notes' => $note,
                'user_id' => $order->user_id,
            ]);
        }
    }

    /**
     * Restock returned items
     */
    public function restockReturnItems(SaleReturn $saleReturn): void
    {
        DB::transaction(function () use ($saleReturn) {
            foreach ($saleReturn->items as $item) {
                $product = Product::lockForUpdate()->findOrFail($item->product_id);
                $qtyToRestock = (float) $item->quantity;

                $product->loadMissing('recipeItems.ingredient');

                if ($product->recipeItems->isNotEmpty()) {
                    foreach ($product->recipeItems as $recipeItem) {
                        $ingredient = Product::lockForUpdate()->find($recipeItem->ingredient_id);
                        if ($ingredient) {
                            $ingredientQtyToRestock = (float) $recipeItem->quantity * $qtyToRestock;
                            $newBalance = (float) $ingredient->current_stock + $ingredientQtyToRestock;
                            $ingredient->current_stock = $newBalance;
                            $ingredient->save();

                            StockMovement::create([
                                'product_id' => $ingredient->id,
                                'movement_type' => 'sale_return',
                                'quantity' => $ingredientQtyToRestock,
                                'unit_cost' => $ingredient->cost_price,
                                'reference_type' => 'SaleReturn',
                                'reference_id' => $saleReturn->id,
                                'balance_after' => $newBalance,
                                'notes' => "Recipe ingredient restock ('{$ingredient->name}') for '{$product->name}' from Return #{$saleReturn->return_number}",
                                'user_id' => $saleReturn->user_id,
                            ]);
                        }
                    }
                } else {
                    $newBalance = (float) $product->current_stock + $qtyToRestock;
                    $product->current_stock = $newBalance;
                    $product->save();

                    StockMovement::create([
                        'product_id' => $product->id,
                        'movement_type' => 'sale_return',
                        'quantity' => $qtyToRestock,
                        'unit_cost' => $product->cost_price,
                        'reference_type' => 'SaleReturn',
                        'reference_id' => $saleReturn->id,
                        'balance_after' => $newBalance,
                        'notes' => "Restocked from Sale Return #{$saleReturn->return_number}",
                        'user_id' => $saleReturn->user_id,
                    ]);
                }
            }
        });
    }

    /**
     * Restock items from a cancelled order back to inventory
     */
    public function restockCancelledOrder(Order $order, string $context = ''): void
    {
        DB::transaction(function () use ($order, $context) {
            foreach ($order->items as $item) {
                $product = Product::lockForUpdate()->findOrFail($item->product_id);
                $qtyToRestock = (float) $item->quantity;

                // Check if package / deal
                $deal = Deal::where('product_id', $product->id)->with('items.product')->first();
                if ($deal && $deal->items->isNotEmpty()) {
                    foreach ($deal->items as $dealItem) {
                        $comp = Product::lockForUpdate()->find($dealItem->product_id);
                        if ($comp) {
                            $compQty = (float) $dealItem->quantity * $qtyToRestock;
                            $this->restockSingleProductOrRecipe($comp, $compQty, $order, $context);
                        }
                    }
                } else {
                    $this->restockSingleProductOrRecipe($product, $qtyToRestock, $order, $context);
                }
            }
        });
    }

    /**
     * Restock a single product or its recipe ingredients
     */
    private function restockSingleProductOrRecipe(Product $product, float $quantity, Order $order, string $context = ''): void
    {
        $product->loadMissing('recipeItems.ingredient');

        if ($product->recipeItems->isNotEmpty()) {
            foreach ($product->recipeItems as $recipeItem) {
                $ingredient = Product::lockForUpdate()->find($recipeItem->ingredient_id);
                if ($ingredient) {
                    $ingQty = (float) $recipeItem->quantity * $quantity;
                    $newBalance = (float) $ingredient->current_stock + $ingQty;
                    $ingredient->current_stock = $newBalance;
                    $ingredient->save();

                    StockMovement::create([
                        'product_id' => $ingredient->id,
                        'movement_type' => 'sale_return',
                        'quantity' => $ingQty,
                        'unit_cost' => $ingredient->cost_price,
                        'reference_type' => 'Order',
                        'reference_id' => $order->id,
                        'balance_after' => $newBalance,
                        'notes' => $context ?: "Recipe ingredient restocked for cancelled Order #{$order->order_number}",
                        'user_id' => auth()->id() ?? $order->user_id,
                    ]);
                }
            }
        } else {
            $newBalance = (float) $product->current_stock + $quantity;
            $product->current_stock = $newBalance;
            $product->save();

            StockMovement::create([
                'product_id' => $product->id,
                'movement_type' => 'sale_return',
                'quantity' => $quantity,
                'unit_cost' => $product->cost_price,
                'reference_type' => 'Order',
                'reference_id' => $order->id,
                'balance_after' => $newBalance,
                'notes' => $context ?: "Restocked for cancelled Order #{$order->order_number}",
                'user_id' => auth()->id() ?? $order->user_id,
            ]);
        }
    }

    /**
     * Record order items as waste/spoilage rather than returning to stock
     */
    public function recordOrderWaste(Order $order, string $reason = ''): void
    {
        DB::transaction(function () use ($order, $reason) {
            foreach ($order->items as $item) {
                StockMovement::create([
                    'product_id' => $item->product_id,
                    'movement_type' => 'adjustment',
                    'quantity' => 0.00, // Stock already deducted at sale, not returned
                    'unit_cost' => (float) $item->unit_cost,
                    'reference_type' => 'Order',
                    'reference_id' => $order->id,
                    'balance_after' => (float) ($item->product?->current_stock ?? 0),
                    'notes' => "Spoilage/Waste: {$reason} (Order #{$order->order_number}, Qty: {$item->quantity})",
                    'user_id' => auth()->id() ?? $order->user_id,
                ]);
            }
        });
    }

    /**
     * Create manual stock adjustment
     */
    public function adjustStock(int $productId, string $type, float $quantity, string $reason, ?int $userId = null): StockAdjustment
    {
        return DB::transaction(function () use ($productId, $type, $quantity, $reason, $userId) {
            $product = Product::lockForUpdate()->findOrFail($productId);
            $signedQty = $type === 'addition' ? abs($quantity) : -abs($quantity);
            $newBalance = (float) $product->current_stock + $signedQty;

            $product->current_stock = $newBalance;
            $product->save();

            $adjustmentNumber = 'ADJ-'.date('Ymd').'-'.rand(1000, 9999);

            $adjustment = StockAdjustment::create([
                'adjustment_number' => $adjustmentNumber,
                'product_id' => $product->id,
                'type' => $type,
                'quantity' => abs($quantity),
                'reason' => $reason,
                'user_id' => $userId ?? auth()->id(),
            ]);

            StockMovement::create([
                'product_id' => $product->id,
                'movement_type' => 'adjustment',
                'quantity' => $signedQty,
                'unit_cost' => $product->cost_price,
                'reference_type' => 'StockAdjustment',
                'reference_id' => $adjustment->id,
                'balance_after' => $newBalance,
                'notes' => "Manual adjustment ({$type}): {$reason}",
                'user_id' => $userId ?? auth()->id(),
            ]);

            return $adjustment;
        });
    }
}
