<?php

namespace App\Services;

use App\Enums\LoyaltyTransactionType;
use App\Models\Customer;
use App\Models\LoyaltyTransaction;
use App\Models\Order;
use App\Models\Restaurant;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class LoyaltyService
{
    public function isEnabled(Restaurant $restaurant): bool
    {
        return (bool) ($restaurant->settings['loyalty_enabled'] ?? false);
    }

    public function pointsPerHundred(Restaurant $restaurant): int
    {
        return max(1, (int) ($restaurant->settings['loyalty_points_per_100'] ?? 1));
    }

    public function pointsForOrderTotal(Restaurant $restaurant, float $total): int
    {
        if ($total <= 0) {
            return 0;
        }

        return (int) floor($total / 100) * $this->pointsPerHundred($restaurant);
    }

    public function rupeesValuePerPoint(Restaurant $restaurant): float
    {
        return max(0.01, (float) ($restaurant->settings['loyalty_rupees_per_point'] ?? 1));
    }

    public function awardForCompletedOrder(Order $order): ?LoyaltyTransaction
    {
        $order->loadMissing(['customer', 'restaurant']);

        if (! $order->customer || ! $this->isEnabled($order->restaurant)) {
            return null;
        }

        $points = $this->pointsForOrderTotal($order->restaurant, (float) $order->total);

        if ($points <= 0) {
            return null;
        }

        return $this->addPoints(
            $order->customer,
            $points,
            LoyaltyTransactionType::Earn,
            $order,
            "Points earned on {$order->order_number}",
        );
    }

    public function redeemPoints(Customer $customer, int $points, ?string $note = null): LoyaltyTransaction
    {
        if ($points <= 0) {
            throw new InvalidArgumentException('Points to redeem must be greater than zero.');
        }

        if ($customer->loyalty_points < $points) {
            throw new InvalidArgumentException('Customer does not have enough loyalty points.');
        }

        return $this->addPoints(
            $customer,
            -$points,
            LoyaltyTransactionType::Redeem,
            null,
            $note ?? 'Points redeemed',
        );
    }

    public function discountForPoints(Restaurant $restaurant, int $points): float
    {
        return round($points * $this->rupeesValuePerPoint($restaurant), 2);
    }

    private function addPoints(
        Customer $customer,
        int $points,
        LoyaltyTransactionType $type,
        ?Order $order,
        ?string $note,
    ): LoyaltyTransaction {
        return DB::transaction(function () use ($customer, $points, $type, $order, $note) {
            $customer = Customer::query()->lockForUpdate()->findOrFail($customer->id);
            $newBalance = $customer->loyalty_points + $points;

            if ($newBalance < 0) {
                throw new InvalidArgumentException('Insufficient loyalty points.');
            }

            $customer->update(['loyalty_points' => $newBalance]);

            return LoyaltyTransaction::create([
                'restaurant_id' => $customer->restaurant_id,
                'customer_id' => $customer->id,
                'order_id' => $order?->id,
                'points' => $points,
                'type' => $type,
                'note' => $note,
            ]);
        });
    }
}
