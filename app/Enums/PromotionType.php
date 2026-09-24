<?php

namespace App\Enums;

enum PromotionType: string
{
    case Percentage = 'percentage';
    case Fixed = 'fixed';

    public function label(): string
    {
        return match ($this) {
            self::Percentage => 'Percentage off',
            self::Fixed => 'Fixed amount off',
        };
    }
}
