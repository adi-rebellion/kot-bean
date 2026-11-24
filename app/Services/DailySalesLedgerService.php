<?php

namespace App\Services;

use App\Enums\PaymentMethod;
use App\Models\DailySalesLedger;
use App\Models\Payment;
use App\Models\Restaurant;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class DailySalesLedgerService
{
    /**
     * @return array{cash: float, online: float, total: float}
     */
    public function posTotalsForDate(Restaurant $restaurant, Carbon $date): array
    {
        $from = $date->copy()->startOfDay();
        $to = $date->copy()->endOfDay();

        $rows = Payment::query()
            ->where('restaurant_id', $restaurant->id)
            ->whereBetween('created_at', [$from, $to])
            ->get();

        $cash = (float) $rows
            ->filter(fn (Payment $p) => $p->method === PaymentMethod::Cash)
            ->sum('amount');

        $online = (float) $rows
            ->filter(fn (Payment $p) => in_array($p->method, [
                PaymentMethod::Upi,
                PaymentMethod::Card,
                PaymentMethod::Other,
            ], true))
            ->sum('amount');

        return [
            'cash' => round($cash, 2),
            'online' => round($online, 2),
            'total' => round($cash + $online, 2),
        ];
    }

    public function upsert(
        Restaurant $restaurant,
        Carbon $saleDate,
        float $cashAmount,
        float $onlineAmount,
        User $user,
        ?string $notes = null,
    ): DailySalesLedger {
        if ($cashAmount < 0 || $onlineAmount < 0) {
            throw new InvalidArgumentException('Amounts cannot be negative.');
        }

        if ($cashAmount + $onlineAmount <= 0) {
            throw new InvalidArgumentException('Enter at least some cash or online sales for this day.');
        }

        return DB::transaction(function () use ($restaurant, $saleDate, $cashAmount, $onlineAmount, $user, $notes) {
            return DailySalesLedger::updateOrCreate(
                [
                    'restaurant_id' => $restaurant->id,
                    'sale_date' => $saleDate->toDateString(),
                ],
                [
                    'cash_amount' => $cashAmount,
                    'online_amount' => $onlineAmount,
                    'notes' => $notes,
                    'recorded_by' => $user->id,
                ],
            );
        });
    }

    public function deleteEntry(DailySalesLedger $entry): void
    {
        $entry->delete();
    }

    /**
     * @return Collection<int, DailySalesLedger>
     */
    public function recentEntries(Restaurant $restaurant, int $limit = 60): Collection
    {
        return DailySalesLedger::query()
            ->with('recordedBy')
            ->where('restaurant_id', $restaurant->id)
            ->orderByDesc('sale_date')
            ->limit($limit)
            ->get();
    }
}