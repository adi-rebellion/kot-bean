<?php

namespace App\Services;

use App\Enums\TableStatus;
use App\Models\Order;
use App\Models\RestaurantTable;
use Illuminate\Support\Facades\DB;

class TableService
{
    public function updateStatus(RestaurantTable $table, TableStatus $status): RestaurantTable
    {
        $table->update(['status' => $status]);

        return $table->fresh();
    }

    public function assignOrder(RestaurantTable $table, Order $order): RestaurantTable
    {
        return DB::transaction(function () use ($table, $order) {
            $order->update(['restaurant_table_id' => $table->id]);

            $table->update(['status' => TableStatus::Occupied]);

            return $table->fresh();
        });
    }

    public function releaseTable(RestaurantTable $table): RestaurantTable
    {
        return DB::transaction(function () use ($table) {
            Order::query()
                ->where('restaurant_table_id', $table->id)
                ->whereNotIn('status', ['completed', 'cancelled'])
                ->update(['restaurant_table_id' => null]);

            $table->update(['status' => TableStatus::Available]);

            return $table->fresh();
        });
    }
}
