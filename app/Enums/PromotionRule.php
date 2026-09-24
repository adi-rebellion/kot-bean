<?php

namespace App\Enums;

enum PromotionRule: string
{
    case None = 'none';
    case FirstCustomerDaily = 'first_customer_daily';
    case FirstOrderEver = 'first_order_ever';

    public function label(): string
    {
        return match ($this) {
            self::None => 'No special rule',
            self::FirstCustomerDaily => 'First customer of the day',
            self::FirstOrderEver => 'Customer\'s first order ever',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::None => 'Available to all eligible orders.',
            self::FirstCustomerDaily => 'Only the first order placed each day qualifies.',
            self::FirstOrderEver => 'Only customers placing their very first order qualify.',
        };
    }
}
