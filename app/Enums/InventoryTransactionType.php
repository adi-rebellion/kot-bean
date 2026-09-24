<?php

namespace App\Enums;

enum InventoryTransactionType: string
{
    case OpeningStock = 'opening_stock';
    case Sale = 'sale';
    case Purchase = 'purchase';
    case Restock = 'restock';
    case Wastage = 'wastage';
    case Return = 'return';
    case Cancellation = 'cancellation';
    case ManualAdjustment = 'manual_adjustment';

    public function label(): string
    {
        return match ($this) {
            self::OpeningStock => 'Opening Stock',
            self::Sale => 'Sale',
            self::Purchase => 'Purchase',
            self::Restock => 'Restock',
            self::Wastage => 'Wastage',
            self::Return => 'Return',
            self::Cancellation => 'Cancellation',
            self::ManualAdjustment => 'Manual Adjustment',
        };
    }
}
