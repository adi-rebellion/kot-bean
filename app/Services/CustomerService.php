<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Order;

class CustomerService
{
    public function __construct(
        private readonly StaffService $staffService,
    ) {}

    public function findOrCreate(int $restaurantId, string $name, string $phone): Customer
    {
        $phone = $this->staffService->normalizePhone($phone);

        $customer = Customer::query()
            ->where('restaurant_id', $restaurantId)
            ->where('phone', $phone)
            ->first();

        if ($customer) {
            if ($customer->name !== $name) {
                $customer->update(['name' => $name]);
            }

            return $customer->fresh();
        }

        return Customer::create([
            'restaurant_id' => $restaurantId,
            'name' => $name,
            'phone' => $phone,
        ]);
    }

    public function recordCompletedOrder(Customer $customer, Order $order): void
    {
        $customer->update([
            'total_orders' => $customer->total_orders + 1,
            'total_spent' => round((float) $customer->total_spent + (float) $order->total, 2),
            'last_order_at' => now(),
        ]);
    }
}
