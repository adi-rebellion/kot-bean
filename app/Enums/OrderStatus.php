<?php

namespace App\Enums;

enum OrderStatus: string
{
    case Draft = 'draft';
    case Confirmed = 'confirmed';
    case KotCreated = 'kot_created';
    case Preparing = 'preparing';
    case Ready = 'ready';
    case Served = 'served';
    case PaymentPending = 'payment_pending';
    case Paid = 'paid';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Confirmed => 'Confirmed',
            self::KotCreated => 'KOT Created',
            self::Preparing => 'Preparing',
            self::Ready => 'Ready',
            self::Served => 'Served',
            self::PaymentPending => 'Payment Pending',
            self::Paid => 'Paid',
            self::Completed => 'Completed',
            self::Cancelled => 'Cancelled',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Draft => 'gray',
            self::Confirmed => 'blue',
            self::KotCreated => 'indigo',
            self::Preparing => 'amber',
            self::Ready => 'emerald',
            self::Served => 'teal',
            self::PaymentPending => 'orange',
            self::Paid => 'green',
            self::Completed => 'green',
            self::Cancelled => 'red',
        };
    }
}
