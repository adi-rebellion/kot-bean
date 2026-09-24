<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use InvalidArgumentException;

class CustomerWhatsAppService
{
    public function __construct(
        private readonly TwilioWhatsAppService $twilioWhatsAppService,
    ) {}

    public function buildLastOrderMessage(Customer $customer, ?Order $order = null, ?string $extraNote = null): string
    {
        $order ??= $customer->latestOrder;

        if (! $order) {
            throw new InvalidArgumentException('Customer has no orders.');
        }

        $order->loadMissing('items');
        $customer->loadMissing('restaurant');

        $timezone = $customer->restaurant->timezone ?? 'Asia/Kolkata';
        $orderDate = ($order->completed_at ?? $order->created_at)->timezone($timezone)->format('M j, Y');

        $items = $order->items
            ->map(fn (OrderItem $item): string => sprintf('%dx %s', $item->quantity, $item->product_name))
            ->implode(', ');

        $lines = [
            "Hi {$customer->name},",
            '',
            "Thank you for dining with us at {$customer->restaurant->name}!",
            '',
            'Here are the details of your last order:',
            "Order: {$order->order_number}",
            "Date: {$orderDate}",
            "Items: {$items}",
            'Total: ₹'.number_format((float) $order->total, 2),
            '',
            'We hope to serve you again soon!',
        ];

        if ($extraNote !== null && trim($extraNote) !== '') {
            $lines[] = '';
            $lines[] = trim($extraNote);
        }

        return implode("\n", $lines);
    }

    public function sendLastOrderMessage(Customer $customer, ?string $extraNote = null): string
    {
        if ($customer->phone === null || trim($customer->phone) === '') {
            throw new InvalidArgumentException('Customer does not have a phone number.');
        }

        $message = $this->buildLastOrderMessage($customer, extraNote: $extraNote);

        return $this->twilioWhatsAppService->send($customer->phone, $message);
    }
}
