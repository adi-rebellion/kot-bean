<?php

namespace App\Support;

class OrderItemModifiers
{
    /**
     * @param  array<int, array{group_id: int, group_name: string, option_id: int, option_name: string, price: float|string}>  $modifiers
     */
    public static function fingerprint(array $modifiers): ?string
    {
        if ($modifiers === []) {
            return null;
        }

        $optionIds = collect($modifiers)
            ->pluck('option_id')
            ->sort()
            ->values()
            ->all();

        return hash('sha256', implode(',', $optionIds));
    }

    /**
     * @param  array<int, array{group_id: int, group_name: string, option_id: int, option_name: string, price: float|string}>  $modifiers
     */
    public static function addonTotal(array $modifiers): float
    {
        return round(collect($modifiers)->sum(fn (array $row) => (float) $row['price']), 2);
    }

    /**
     * @param  array<int, array{group_id: int, group_name: string, option_id: int, option_name: string, price: float|string}>|null  $modifiers
     */
    public static function kitchenNotes(?array $modifiers): ?string
    {
        if ($modifiers === null || $modifiers === []) {
            return null;
        }

        $lines = collect($modifiers)->map(function (array $row): string {
            $price = (float) $row['price'];
            $suffix = $price > 0 ? ' (+₹'.number_format($price, 0).')' : '';

            return $row['group_name'].': '.$row['option_name'].$suffix;
        });

        return $lines->implode('; ');
    }
}
