<?php

namespace App\Enums;

enum LoyaltyTransactionType: string
{
    case Earn = 'earn';
    case Redeem = 'redeem';
    case Adjust = 'adjust';

    public function label(): string
    {
        return match ($this) {
            self::Earn => 'Earned',
            self::Redeem => 'Redeemed',
            self::Adjust => 'Adjusted',
        };
    }
}
