<?php

namespace App\Support;

class OrderItemModifiers
{
    /**
     * @param  array<int, array{addon_id: int, name: string, price: float|string}>  $modifiers
     */
    public static function fingerprint(array $modifiers): ?string
    {
        if ($modifiers === []) {
            return null;
        }

        $addonIds = collect($modifiers)
            ->map(fn (array $row) => $row['addon_id'] ?? $row['option_id'] ?? null)
            ->filter()
            ->sort()
            ->values()
            ->all();

        return hash('sha256', implode(',', $addonIds));
    }

    /**
     * @param  array<int, array{addon_id: int, name: string, price: float|string}>  $modifiers
     */
    public static function addonTotal(array $modifiers): float
    {
        return round(collect($modifiers)->sum(fn (array $row) => (float) $row['price']), 2);
    }

    /**
     * @param  array<int, array{addon_id: int, name: string, price: float|string}>|null  $modifiers
     */
    public static function kitchenNotes(?array $modifiers): ?string
    {
        if ($modifiers === null || $modifiers === []) {
            return null;
        }

        $lines = collect($modifiers)->map(function (array $row): string {
            $price = (float) $row['price'];
            $name = $row['name'] ?? $row['option_name'] ?? '';
            $suffix = $price > 0 ? ' (+₹'.number_format($price, 0).')' : '';

            return $name.$suffix;
        });

        return $lines->implode('; ');
    }
}
