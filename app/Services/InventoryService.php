<?php

namespace App\Services;

use App\Enums\InventoryTransactionType;
use App\Exceptions\InsufficientStockException;
use App\Models\InventoryTransaction;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Restaurant;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class InventoryService
{
    public function adjustStock(
        Product $product,
        int $quantity,
        InventoryTransactionType $type,
        ?User $user = null,
        ?Order $order = null,
        ?string $reason = null,
        ?ProductVariant $variant = null,
    ): InventoryTransaction {
        if (! $product->track_inventory) {
            throw new \InvalidArgumentException("Product [{$product->id}] does not track inventory.");
        }

        return DB::transaction(function () use ($product, $quantity, $type, $user, $order, $reason, $variant) {
            $product = Product::query()->lockForUpdate()->findOrFail($product->id);

            if ($variant) {
                $variant = ProductVariant::query()
                    ->where('product_id', $product->id)
                    ->lockForUpdate()
                    ->findOrFail($variant->id);
            }

            $previousStock = $variant?->stock ?? $product->stock;
            $delta = $this->resolveStockDelta($type, $quantity);
            $newStock = $previousStock + $delta;

            if ($newStock < 0) {
                throw new InsufficientStockException($product, abs($delta), $previousStock, $variant);
            }

            if ($variant) {
                $variant->update(['stock' => $newStock]);
            } else {
                $product->update(['stock' => $newStock]);
            }

            return InventoryTransaction::create([
                'restaurant_id' => $product->restaurant_id,
                'product_id' => $product->id,
                'product_variant_id' => $variant?->id,
                'type' => $type,
                'quantity' => abs($quantity),
                'previous_stock' => $previousStock,
                'new_stock' => $newStock,
                'order_id' => $order?->id,
                'user_id' => $user?->id,
                'reason' => $reason,
            ]);
        });
    }

    public function deductForOrderItem(OrderItem $item, User $user): void
    {
        $item->loadMissing(['product', 'variant', 'order']);

        if (! $item->product->track_inventory) {
            return;
        }

        DB::transaction(function () use ($item, $user) {
            $product = Product::query()->lockForUpdate()->findOrFail($item->product_id);

            $variant = null;
            if ($item->product_variant_id) {
                $variant = ProductVariant::query()
                    ->where('product_id', $product->id)
                    ->lockForUpdate()
                    ->findOrFail($item->product_variant_id);
            }

            $available = $variant?->stock ?? $product->stock;

            if ($available < $item->quantity) {
                throw new InsufficientStockException($product, $item->quantity, $available, $variant);
            }

            $this->adjustStock(
                $product,
                $item->quantity,
                InventoryTransactionType::Sale,
                $user,
                $item->order,
                'Order item sale',
                $variant,
            );
        });
    }

    public function checkAvailability(Product $product, int $qty, ?ProductVariant $variant = null): bool
    {
        if (! $product->track_inventory) {
            return true;
        }

        if (! $product->is_available) {
            return false;
        }

        if ($variant) {
            return $variant->is_available && $variant->stock >= $qty;
        }

        if ($product->has_variants) {
            return $product->variants()
                ->where('is_available', true)
                ->where('stock', '>=', $qty)
                ->exists();
        }

        return $product->stock >= $qty;
    }

    public function getLowStockProducts(Restaurant $restaurant): Collection
    {
        return Product::query()
            ->where('restaurant_id', $restaurant->id)
            ->where('track_inventory', true)
            ->whereColumn('stock', '<=', 'min_stock')
            ->orderBy('stock')
            ->get();
    }

    private function resolveStockDelta(InventoryTransactionType $type, int $quantity): int
    {
        $amount = abs($quantity);

        return match ($type) {
            InventoryTransactionType::Sale,
            InventoryTransactionType::Wastage => -$amount,
            InventoryTransactionType::Purchase,
            InventoryTransactionType::Restock,
            InventoryTransactionType::Return,
            InventoryTransactionType::Cancellation,
            InventoryTransactionType::OpeningStock => $amount,
            InventoryTransactionType::ManualAdjustment => $quantity,
        };
    }
}
