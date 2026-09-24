<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Enums\PromotionRule;
use App\Enums\PromotionType;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Promotion;
use App\Models\PromotionRedemption;
use App\Models\Restaurant;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class PromotionService
{
    public function create(array $data, Restaurant $restaurant): Promotion
    {
        return Promotion::create([
            'restaurant_id' => $restaurant->id,
            ...$this->normalizeData($data),
        ]);
    }

    public function update(Promotion $promotion, array $data): Promotion
    {
        $promotion->update($this->normalizeData($data));

        return $promotion->fresh();
    }

    public function delete(Promotion $promotion): void
    {
        if ($promotion->orders()->whereNotIn('status', [OrderStatus::Draft, OrderStatus::Cancelled])->exists()) {
            throw new InvalidArgumentException('Cannot delete a promotion that has been used on completed orders.');
        }

        $promotion->delete();
    }

    public function findByCode(int $restaurantId, string $code): ?Promotion
    {
        $code = strtoupper(trim($code));

        if ($code === '') {
            return null;
        }

        return Promotion::query()
            ->where('restaurant_id', $restaurantId)
            ->where('code', $code)
            ->first();
    }

    public function findBestAutoApplyPromotion(Order $order, ?Customer $customer = null): ?Promotion
    {
        $promotions = Promotion::query()
            ->where('auto_apply', true)
            ->where('is_active', true)
            ->get();

        return $this->pickBestEligible($promotions, $order, $customer);
    }

    public function isEligible(Promotion $promotion, Order $order, ?Customer $customer = null): bool
    {
        if (! $promotion->is_active || $promotion->isExpired() || ! $promotion->hasUsesRemaining()) {
            return false;
        }

        if ($order->items->isEmpty()) {
            return false;
        }

        $order->loadMissing('items');

        $preDiscountTotal = $this->preDiscountTotal($order);

        if ($promotion->min_order_amount !== null && $preDiscountTotal < (float) $promotion->min_order_amount) {
            return false;
        }

        return match ($promotion->rule) {
            PromotionRule::None => true,
            PromotionRule::FirstCustomerDaily => $this->isFirstCustomerOfDay($promotion),
            PromotionRule::FirstOrderEver => $this->isFirstOrderEver($customer),
        };
    }

    public function calculateDiscountAmount(Promotion $promotion, Order $order): float
    {
        $order->loadMissing('items');
        $base = $this->preDiscountTotal($order);

        $discount = match ($promotion->type) {
            PromotionType::Percentage => round($base * ((float) $promotion->value / 100), 2),
            PromotionType::Fixed => round((float) $promotion->value, 2),
        };

        return min($discount, $base);
    }

    public function applyToOrder(Order $order, Promotion $promotion, ?Customer $customer = null): Order
    {
        if ($order->status !== OrderStatus::Draft) {
            throw new InvalidArgumentException('Promotions can only be applied to draft orders.');
        }

        if (! $this->isEligible($promotion, $order, $customer)) {
            throw new InvalidArgumentException('This promotion is not eligible for the current order.');
        }

        $discountAmount = $this->calculateDiscountAmount($promotion, $order);

        $order->update([
            'promotion_id' => $promotion->id,
            'discount_amount' => $discountAmount,
        ]);

        return app(OrderService::class)->recalculateTotals($order);
    }

    public function removeFromOrder(Order $order): Order
    {
        if ($order->status !== OrderStatus::Draft) {
            throw new InvalidArgumentException('Promotions can only be removed from draft orders.');
        }

        $order->update([
            'promotion_id' => null,
            'discount_amount' => 0,
        ]);

        return app(OrderService::class)->recalculateTotals($order);
    }

    public function syncAutoApply(Order $order, ?Customer $customer = null): Order
    {
        if ($order->status !== OrderStatus::Draft) {
            return $order;
        }

        $order->loadMissing(['items', 'promotion']);

        if ($order->promotion_id && ! $order->promotion?->auto_apply) {
            return $order;
        }

        $best = $this->findBestAutoApplyPromotion($order, $customer);

        if (! $best) {
            if ($order->promotion_id) {
                return $this->removeFromOrder($order);
            }

            return $order;
        }

        if ($order->promotion_id === $best->id) {
            $discountAmount = $this->calculateDiscountAmount($best, $order);

            if ((float) $order->discount_amount !== $discountAmount) {
                $order->update(['discount_amount' => $discountAmount]);

                return app(OrderService::class)->recalculateTotals($order);
            }

            return $order;
        }

        return $this->applyToOrder($order, $best, $customer);
    }

    public function recordRedemption(Order $order): void
    {
        if (! $order->promotion_id || (float) $order->discount_amount <= 0) {
            return;
        }

        DB::transaction(function () use ($order) {
            $exists = PromotionRedemption::query()
                ->where('order_id', $order->id)
                ->exists();

            if ($exists) {
                return;
            }

            PromotionRedemption::create([
                'restaurant_id' => $order->restaurant_id,
                'promotion_id' => $order->promotion_id,
                'order_id' => $order->id,
                'customer_id' => $order->customer_id,
                'discount_amount' => $order->discount_amount,
                'redeemed_at' => now(),
            ]);

            Promotion::query()
                ->whereKey($order->promotion_id)
                ->increment('uses_count');
        });
    }

    /**
     * @param  Collection<int, Promotion>  $promotions
     */
    private function pickBestEligible(Collection $promotions, Order $order, ?Customer $customer): ?Promotion
    {
        return $promotions
            ->filter(fn (Promotion $promotion): bool => $this->isEligible($promotion, $order, $customer))
            ->sortByDesc(fn (Promotion $promotion): float => $this->calculateDiscountAmount($promotion, $order))
            ->first();
    }

    private function preDiscountTotal(Order $order): float
    {
        $subtotal = $order->items->sum(
            fn ($item) => ((float) $item->unit_price * $item->quantity) - (float) $item->discount_amount
        );
        $taxAmount = $order->items->sum(fn ($item) => (float) $item->tax_amount);

        return round($subtotal + $taxAmount, 2);
    }

    private function isFirstCustomerOfDay(Promotion $promotion): bool
    {
        return ! PromotionRedemption::query()
            ->where('promotion_id', $promotion->id)
            ->whereDate('redeemed_at', today())
            ->exists();
    }

    private function isFirstOrderEver(?Customer $customer): bool
    {
        if (! $customer) {
            return false;
        }

        return $customer->total_orders === 0;
    }

    /**
     * @return array<string, mixed>
     */
    private function normalizeData(array $data): array
    {
        if (array_key_exists('code', $data)) {
            $code = trim((string) $data['code']);
            $data['code'] = $code !== '' ? strtoupper($code) : null;
        }

        if (array_key_exists('expires_at', $data) && $data['expires_at'] === '') {
            $data['expires_at'] = null;
        }

        if (array_key_exists('max_uses', $data) && ($data['max_uses'] === '' || $data['max_uses'] === null)) {
            $data['max_uses'] = null;
        }

        if (array_key_exists('min_order_amount', $data) && ($data['min_order_amount'] === '' || $data['min_order_amount'] === null)) {
            $data['min_order_amount'] = null;
        }

        return $data;
    }
}
