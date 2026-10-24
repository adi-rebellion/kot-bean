<?php

namespace App\Support;

class MenuProductFields
{
    /** @return array<string, string> */
    public static function labels(): array
    {
        return [
            'description' => 'Description',
            'sku' => 'SKU',
            'cost_price' => 'Cost price',
            'tax_rate' => 'Item tax rate',
            'preparation_time' => 'Prep time',
            'inventory' => 'Inventory tracking & stock',
            'variants' => 'Size / variants',
            'addons' => 'Customizations (e.g. Iced, Extra cheese)',
            'sort_order' => 'Sort order',
            'image' => 'Product photo & AI image',
        ];
    }

    /** @return array<string, bool> */
    public static function defaults(): array
    {
        return array_fill_keys(array_keys(self::labels()), true);
    }

    /**
     * @param  array<string, mixed>|null  $stored
     * @return array<string, bool>
     */
    public static function resolve(?array $stored): array
    {
        $merged = array_merge(self::defaults(), $stored ?? []);

        return array_map(fn ($value) => (bool) $value, $merged);
    }

    public static function enabled(?array $stored, string $field): bool
    {
        return self::resolve($stored)[$field] ?? true;
    }
}
