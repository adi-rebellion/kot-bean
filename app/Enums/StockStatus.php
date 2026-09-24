<?php

namespace App\Enums;

enum StockStatus: string
{
    case InStock = 'in_stock';
    case LowStock = 'low_stock';
    case Critical = 'critical';
    case OutOfStock = 'out_of_stock';

    public function label(): string
    {
        return match ($this) {
            self::InStock => 'In Stock',
            self::LowStock => 'Low Stock',
            self::Critical => 'Critical',
            self::OutOfStock => 'Out of Stock',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::InStock => 'emerald',
            self::LowStock => 'amber',
            self::Critical => 'orange',
            self::OutOfStock => 'red',
        };
    }

    public static function fromStock(int $stock, int $minimumStock): self
    {
        if ($stock <= 0) {
            return self::OutOfStock;
        }

        if ($stock <= max(1, (int) floor($minimumStock / 2))) {
            return self::Critical;
        }

        if ($stock <= $minimumStock) {
            return self::LowStock;
        }

        return self::InStock;
    }
}
