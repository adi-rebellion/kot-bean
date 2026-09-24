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

    /**
     * Template variables for Twilio ContentSid ({{1}}–{{7}}):
     * 1 = customer name, 2 = restaurant, 3 = order number,
     * 4 = date, 5 = items, 6 = total, 7 = optional note
     *
     * @return array<string, string>
     */
    public function buildTemplateVariables(Customer $customer, ?Order $order = null, ?string $extraNote = null): array
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

        return [
            '1' => $customer->name,
            '2' => $customer->restaurant->name,
            '3' => $order->order_number,
            '4' => $orderDate,
            '5' => $items,
            '6' => '₹'.number_format((float) $order->total, 2),
            '7' => ($extraNote !== null && trim($extraNote) !== '') ? trim($extraNote) : 'We hope to serve you again soon!',
        ];
    }

    public function buildLastOrderMessage(Customer $customer, ?Order $order = null, ?string $extraNote = null): string
    {
        $variables = $this->buildTemplateVariables($customer, $order, $extraNote);

        $lines = [
            "Hi {$variables['1']},",
            '',
            "Thank you for dining with us at {$variables['2']}!",
            '',
            'Here are the details of your last order:',
            "Order: {$variables['3']}",
            "Date: {$variables['4']}",
            "Items: {$variables['5']}",
            "Total: {$variables['6']}",
            '',
            $variables['7'],
        ];

        return implode("\n", $lines);
    }

    public function sendLastOrderMessage(Customer $customer, ?string $extraNote = null): string
    {
        if ($customer->phone === null || trim($customer->phone) === '') {
            throw new InvalidArgumentException('Customer does not have a phone number.');
        }

        $variables = $this->buildTemplateVariables($customer, extraNote: $extraNote);

        return $this->twilioWhatsAppService->sendTemplate($customer->phone, $variables);
    }
}
