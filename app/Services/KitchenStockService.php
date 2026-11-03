<?php

namespace App\Services;

use App\Models\Restaurant;
use App\Models\StockDailyRecord;
use App\Models\StockItem;
use App\Models\StockItemVendorPrice;
use App\Models\User;
use App\Models\Vendor;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class KitchenStockService
{
    public function recordOpening(StockItem $item, float $quantity, User $user): StockDailyRecord
    {
        if ($quantity < 0) {
            throw new InvalidArgumentException('Opening quantity cannot be negative.');
        }

        return DB::transaction(function () use ($item, $quantity, $user) {
            $record = $this->todayRecord($item, true);
            $record->update([
                'opening_quantity' => $quantity,
                'recorded_by' => $user->id,
            ]);

            if ($record->closing_quantity === null) {
                $item->update(['current_quantity' => $quantity]);
            }

            return $record->fresh();
        });
    }

    public function recordRestock(StockItem $item, float $quantity, User $user, ?string $notes = null): StockDailyRecord
    {
        if ($quantity <= 0) {
            throw new InvalidArgumentException('Restock quantity must be greater than zero.');
        }

        return DB::transaction(function () use ($item, $quantity, $user, $notes) {
            $record = $this->todayRecord($item, true);
            $record->update([
                'restock_quantity' => (float) $record->restock_quantity + $quantity,
                'recorded_by' => $user->id,
                'notes' => $notes ?? $record->notes,
            ]);

            if ($record->closing_quantity === null) {
                $item->update([
                    'current_quantity' => (float) $item->current_quantity + $quantity,
                ]);
            }

            return $record->fresh();
        });
    }

    public function recordClosing(StockItem $item, float $quantity, User $user, ?string $notes = null): StockDailyRecord
    {
        if ($quantity < 0) {
            throw new InvalidArgumentException('Closing quantity cannot be negative.');
        }

        return DB::transaction(function () use ($item, $quantity, $user, $notes) {
            $record = $this->todayRecord($item, true);
            $used = max(0, (float) $record->opening_quantity + (float) $record->restock_quantity - $quantity);

            $record->update([
                'closing_quantity' => $quantity,
                'used_quantity' => $used,
                'recorded_by' => $user->id,
                'notes' => $notes ?? $record->notes,
            ]);

            $item->update(['current_quantity' => $quantity]);

            return $record->fresh();
        });
    }

    public function suggestedOpeningQuantity(StockItem $item): ?float
    {
        $yesterday = StockDailyRecord::query()
            ->where('stock_item_id', $item->id)
            ->where('record_date', today()->subDay())
            ->whereNotNull('closing_quantity')
            ->first();

        if ($yesterday) {
            return (float) $yesterday->closing_quantity;
        }

        return $item->current_quantity > 0 ? (float) $item->current_quantity : null;
    }

    /**
     * @return Collection<int, array{
     *     stock_item: StockItem,
     *     message: string,
     *     last_weekday_usage: float|null,
     *     tomorrow_weekday: string,
     *     best_vendor: StockItemVendorPrice|null,
     * }>
     */
    public function restockAlerts(Restaurant $restaurant): Collection
    {
        $tomorrow = today()->addDay();
        $tomorrowWeekday = $tomorrow->format('l');

        return StockItem::query()
            ->where('restaurant_id', $restaurant->id)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->map(function (StockItem $item) use ($tomorrow, $tomorrowWeekday) {
                $lastUsage = $this->lastUsageOnWeekday($item, (int) $tomorrow->dayOfWeek);

                if ($lastUsage === null || $lastUsage <= (float) $item->current_quantity) {
                    return null;
                }

                $bestVendor = $this->bestVendorQuote($item);

                $message = sprintf(
                    'Tomorrow is %s. Last %s you used %s %s of %s but only %s %s left — please restock.',
                    $tomorrowWeekday,
                    $tomorrowWeekday,
                    $this->formatQty($lastUsage),
                    $item->unit,
                    $item->name,
                    $this->formatQty((float) $item->current_quantity),
                    $item->unit,
                );

                return [
                    'stock_item' => $item,
                    'message' => $message,
                    'last_weekday_usage' => $lastUsage,
                    'tomorrow_weekday' => $tomorrowWeekday,
                    'best_vendor' => $bestVendor,
                ];
            })
            ->filter()
            ->values();
    }

    public function lastUsageOnWeekday(StockItem $item, int $dayOfWeek): ?float
    {
        $record = StockDailyRecord::query()
            ->where('stock_item_id', $item->id)
            ->whereNotNull('used_quantity')
            ->where('record_date', '<', today())
            ->orderByDesc('record_date')
            ->get()
            ->first(fn (StockDailyRecord $row) => $row->record_date->dayOfWeek === $dayOfWeek);

        return $record ? (float) $record->used_quantity : null;
    }

    public function bestVendorQuote(StockItem $item): ?StockItemVendorPrice
    {
        return StockItemVendorPrice::query()
            ->with('vendor')
            ->where('stock_item_id', $item->id)
            ->whereHas('vendor', fn ($query) => $query->where('is_active', true))
            ->orderBy('unit_price')
            ->first();
    }

    public function telLink(?Vendor $vendor): ?string
    {
        if (! $vendor?->phone) {
            return null;
        }

        $digits = preg_replace('/\D+/', '', $vendor->phone);

        if ($digits === '') {
            return null;
        }

        if (strlen($digits) === 10) {
            $digits = '91'.$digits;
        }

        return 'tel:+'.$digits;
    }

    private function todayRecord(StockItem $item, bool $create): StockDailyRecord
    {
        $existing = StockDailyRecord::query()
            ->where('stock_item_id', $item->id)
            ->whereDate('record_date', today())
            ->first();

        if ($existing) {
            return $existing;
        }

        if (! $create) {
            throw new InvalidArgumentException('No stock record for today yet.');
        }

        $opening = $this->suggestedOpeningQuantity($item) ?? (float) $item->current_quantity;

        return StockDailyRecord::create([
            'stock_item_id' => $item->id,
            'record_date' => today(),
            'opening_quantity' => $opening,
            'restock_quantity' => 0,
        ]);
    }

    private function formatQty(float $value): string
    {
        return fmod($value, 1.0) === 0.0 ? (string) (int) $value : number_format($value, 1);
    }
}
