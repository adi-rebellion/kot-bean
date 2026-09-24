<?php

namespace App\Services;

use App\Enums\InventoryTransactionType;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class PaymentService
{
    public function __construct(
        private readonly InventoryService $inventoryService,
    ) {}

    public function recordPayment(
        Order $order,
        PaymentMethod $method,
        float $amount,
        User $user,
        ?string $reference = null,
    ): Payment {
        if ($amount <= 0) {
            throw new InvalidArgumentException('Payment amount must be greater than zero.');
        }

        if (in_array($order->status, [OrderStatus::Draft, OrderStatus::Cancelled, OrderStatus::Completed], true)) {
            throw new InvalidArgumentException('Payments cannot be recorded for this order status.');
        }

        return DB::transaction(function () use ($order, $method, $amount, $user, $reference) {
            $payment = Payment::create([
                'restaurant_id' => $order->restaurant_id,
                'order_id' => $order->id,
                'payment_number' => $this->generatePaymentNumber($order->restaurant_id),
                'method' => $method,
                'amount' => round($amount, 2),
                'reference' => $reference,
                'received_by' => $user->id,
            ]);

            $paidTotal = (float) $order->payments()->sum('amount');

            if ($paidTotal >= (float) $order->total) {
                $order->update(['status' => OrderStatus::Paid]);
            } elseif ($order->status !== OrderStatus::PaymentPending) {
                $order->update(['status' => OrderStatus::PaymentPending]);
            }

            return $payment;
        });
    }

    public function completeOrder(Order $order): Order
    {
        if (! in_array($order->status, [OrderStatus::Paid, OrderStatus::PaymentPending, OrderStatus::Served], true)) {
            throw new InvalidArgumentException('Order cannot be completed in its current status.');
        }

        return DB::transaction(function () use ($order) {
            $order->load(['items.product', 'items.variant', 'creator', 'server']);

            $actingUser = $order->server ?? $order->creator;

            foreach ($order->items as $item) {
                if ($item->product->track_inventory) {
                    $alreadyDeducted = $order->inventoryTransactions()
                        ->where('product_id', $item->product_id)
                        ->where('product_variant_id', $item->product_variant_id)
                        ->where('type', InventoryTransactionType::Sale)
                        ->exists();

                    if (! $alreadyDeducted && $actingUser) {
                        $this->inventoryService->deductForOrderItem(
                            $item,
                            $actingUser,
                        );
                    }
                }
            }

            $order->update([
                'status' => OrderStatus::Completed,
                'completed_at' => now(),
            ]);

            if ($order->customer_id) {
                $order->loadMissing('customer');

                if ($order->customer) {
                    app(CustomerService::class)->recordCompletedOrder($order->customer, $order);
                }
            }

            app(PromotionService::class)->recordRedemption($order);

            if ($order->restaurant_table_id) {
                app(TableService::class)->releaseTable($order->restaurantTable);
            }

            return $order->fresh();
        });
    }

    private function generatePaymentNumber(int $restaurantId): string
    {
        $sequence = Payment::withoutGlobalScopes()
            ->where('restaurant_id', $restaurantId)
            ->count() + 1;

        return 'PAY-'.str_pad((string) $sequence, 6, '0', STR_PAD_LEFT);
    }
}
