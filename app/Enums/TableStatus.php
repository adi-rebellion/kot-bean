<?php

namespace App\Enums;

enum TableStatus: string
{
    case Available = 'available';
    case Occupied = 'occupied';
    case Preparing = 'preparing';
    case Reserved = 'reserved';

    public function label(): string
    {
        return match ($this) {
            self::Available => 'Available',
            self::Occupied => 'Occupied',
            self::Preparing => 'Preparing',
            self::Reserved => 'Reserved',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Available => 'emerald',
            self::Occupied => 'amber',
            self::Preparing => 'blue',
            self::Reserved => 'purple',
        };
    }
}
