<?php

namespace App\Services;

use App\Enums\InventoryTransactionType;
use App\Enums\KotStatus;
use App\Enums\OrderStatus;
use App\Enums\OrderType;
use App\Exceptions\InsufficientStockException;
use App\Models\Kot;
use App\Models\KotItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\RestaurantTable;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class OrderService
{
    public function __construct(
        private readonly InventoryService $inventoryService,
    ) {}

    public function createDraft(array $data, User $user): Order
    {
        return DB::transaction(function () use ($data, $user) {
            $restaurantId = $user->restaurant_id;

            $order = Order::create([
                'restaurant_id' => $restaurantId,
                'order_number' => $this->generateOrderNumber($restaurantId),
                'restaurant_table_id' => $data['restaurant_table_id'] ?? null,
                'customer_id' => $data['customer_id'] ?? null,
                'created_by' => $user->id,
                'type' => $data['type'] ?? OrderType::DineIn,
                'status' => OrderStatus::Draft,
                'notes' => $data['notes'] ?? null,
                'delivery_address' => $data['delivery_address'] ?? null,
                'delivery_phone' => $data['delivery_phone'] ?? null,
            ]);

            if ($order->restaurant_table_id) {
                $table = RestaurantTable::findOrFail($order->restaurant_table_id);
                app(TableService::class)->assignOrder($table, $order);
            }

            return $order;
        });
    }

    public function addItem(
        Order $order,
        Product $product,
        int $quantity,
        ?ProductVariant $variant = null,
        ?string $notes = null,
    ): OrderItem {
        $this->assertDraftOrder($order);

        if ($quantity < 1) {
            throw new InvalidArgumentException('Quantity must be at least 1.');
        }

        if (! $this->inventoryService->checkAvailability($product, $quantity, $variant)) {
            $available = $variant?->stock ?? $product->stock;

            throw new InsufficientStockException($product, $quantity, $available, $variant);
        }

        return DB::transaction(function () use ($order, $product, $quantity, $variant, $notes) {
            $order->loadMissing('restaurant');

            $unitPrice = (float) ($variant?->price ?? $product->price);
            $taxRate = (float) ($product->tax_rate ?? $order->restaurant->default_tax_rate ?? 0);
            $lineSubtotal = round($unitPrice * $quantity, 2);
            $taxAmount = round($lineSubtotal * ($taxRate / 100), 2);
            $total = round($lineSubtotal + $taxAmount, 2);

            $item = OrderItem::create([
                'order_id' => $order->id,
                'product_id' => $product->id,
                'product_variant_id' => $variant?->id,
                'product_name' => $product->name,
                'variant_name' => $variant?->name,
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'tax_rate' => $taxRate,
                'tax_amount' => $taxAmount,
                'total' => $total,
                'notes' => $notes,
            ]);

            $this->recalculateTotals($order);

            return $item->fresh();
        });
    }

    public function removeItem(OrderItem $item): void
    {
        $order = $item->order;
        $this->assertDraftOrder($order);

        DB::transaction(function () use ($item, $order) {
            $item->delete();
            $this->recalculateTotals($order);
        });
    }

    public function updateItemQuantity(OrderItem $item, int $quantity): OrderItem
    {
        $order = $item->order;
        $this->assertDraftOrder($order);

        if ($quantity < 1) {
            throw new InvalidArgumentException('Quantity must be at least 1.');
        }

        $item->loadMissing(['product', 'variant']);

        if (! $this->inventoryService->checkAvailability($item->product, $quantity, $item->variant)) {
            $available = $item->variant?->stock ?? $item->product->stock;

            throw new InsufficientStockException($item->product, $quantity, $available, $item->variant);
        }

        return DB::transaction(function () use ($item, $order, $quantity) {
            $lineSubtotal = round((float) $item->unit_price * $quantity, 2);
            $taxAmount = round($lineSubtotal * ((float) $item->tax_rate / 100), 2);

            $item->update([
                'quantity' => $quantity,
                'tax_amount' => $taxAmount,
                'total' => round($lineSubtotal + $taxAmount - (float) $item->discount_amount, 2),
            ]);

            $this->recalculateTotals($order);

            return $item->fresh();
        });
    }

    public function confirm(Order $order): Order
    {
        if ($order->status !== OrderStatus::Draft) {
            throw new InvalidArgumentException('Only draft orders can be confirmed.');
        }

        if ($order->items()->count() === 0) {
            throw new InvalidArgumentException('Cannot confirm an order with no items.');
        }

        $order->update([
            'status' => OrderStatus::Confirmed,
            'confirmed_at' => now(),
        ]);

        return $order->fresh();
    }

    public function sendToKitchen(Order $order, User $user): Kot
    {
        if (! in_array($order->status, [OrderStatus::Confirmed, OrderStatus::KotCreated], true)) {
            throw new InvalidArgumentException('Order must be confirmed before sending to kitchen.');
        }

        return DB::transaction(function () use ($order, $user) {
            $order->load(['items' => fn ($query) => $query->where('status', 'pending')]);

            if ($order->items->isEmpty()) {
                throw new InvalidArgumentException('No pending items to send to kitchen.');
            }

            $kot = Kot::create([
                'restaurant_id' => $order->restaurant_id,
                'order_id' => $order->id,
                'kot_number' => $this->generateKotNumber($order->restaurant_id),
                'status' => KotStatus::Pending,
                'restaurant_table_id' => $order->restaurant_table_id,
                'created_by' => $user->id,
                'notes' => $order->notes,
            ]);

            foreach ($order->items as $item) {
                KotItem::create([
                    'kot_id' => $kot->id,
                    'order_item_id' => $item->id,
                    'product_name' => $item->product_name,
                    'variant_name' => $item->variant_name,
                    'quantity' => $item->quantity,
                    'notes' => $item->notes,
                ]);

                $item->update(['status' => 'sent']);
            }

            $order->update(['status' => OrderStatus::KotCreated]);

            return $kot->load('items');
        });
    }

    public function cancel(Order $order, User $user, ?string $reason = null): Order
    {
        if ($order->status === OrderStatus::Cancelled) {
            throw new InvalidArgumentException('Order is already cancelled.');
        }

        if (in_array($order->status, [OrderStatus::Completed, OrderStatus::Paid], true)) {
            throw new InvalidArgumentException('Completed or paid orders cannot be cancelled.');
        }

        return DB::transaction(function () use ($order, $user, $reason) {
            $this->restoreInventoryIfNeeded($order, $user, $reason);

            $order->update([
                'status' => OrderStatus::Cancelled,
                'cancelled_at' => now(),
                'notes' => trim(($order->notes ?? '').($reason ? "\nCancelled: {$reason}" : '')),
            ]);

            if ($order->restaurant_table_id) {
                app(TableService::class)->releaseTable($order->restaurantTable);
            }

            Kot::query()
                ->where('order_id', $order->id)
                ->whereNot('status', KotStatus::Cancelled)
                ->update(['status' => KotStatus::Cancelled]);

            return $order->fresh();
        });
    }

    public function recalculateTotals(Order $order): Order
    {
        $order->load('items');

        $subtotal = $order->items->sum(
            fn (OrderItem $item) => ((float) $item->unit_price * $item->quantity) - (float) $item->discount_amount
        );
        $taxAmount = $order->items->sum(fn (OrderItem $item) => (float) $item->tax_amount);
        $total = round($subtotal + $taxAmount - (float) $order->discount_amount, 2);

        $order->update([
            'subtotal' => round($subtotal, 2),
            'tax_amount' => round($taxAmount, 2),
            'total' => $total,
        ]);

        return $order->fresh();
    }

    private function assertDraftOrder(Order $order): void
    {
        if ($order->status !== OrderStatus::Draft) {
            throw new InvalidArgumentException('Order items can only be modified while in draft status.');
        }
    }

    private function restoreInventoryIfNeeded(Order $order, User $user, ?string $reason): void
    {
        $order->load('items.product');

        if (! $order->inventoryTransactions()
            ->where('type', InventoryTransactionType::Sale)
            ->exists()) {
            return;
        }

        foreach ($order->items as $item) {
            if (! $item->product->track_inventory) {
                continue;
            }

            $this->inventoryService->adjustStock(
                $item->product,
                $item->quantity,
                InventoryTransactionType::Cancellation,
                $user,
                $order,
                $reason ?? 'Order cancelled',
                $item->variant,
            );
        }
    }

    private function generateOrderNumber(int $restaurantId): string
    {
        $sequence = Order::withoutGlobalScopes()
            ->where('restaurant_id', $restaurantId)
            ->count() + 1;

        return 'ORD-'.str_pad((string) $sequence, 6, '0', STR_PAD_LEFT);
    }

    private function generateKotNumber(int $restaurantId): string
    {
        $sequence = Kot::withoutGlobalScopes()
            ->where('restaurant_id', $restaurantId)
            ->count() + 1;

        return 'KOT-'.str_pad((string) $sequence, 6, '0', STR_PAD_LEFT);
    }
}
