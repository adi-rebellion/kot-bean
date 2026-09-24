<?php

namespace App\Services;

use App\Enums\PaymentMethod;
use App\Models\CashRegisterSession;
use App\Models\Payment;
use App\Models\Restaurant;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class CashRegisterService
{
    public function getTodaySession(Restaurant $restaurant): ?CashRegisterSession
    {
        return CashRegisterSession::query()
            ->where('restaurant_id', $restaurant->id)
            ->whereDate('session_date', today())
            ->first();
    }

    public function openSession(Restaurant $restaurant, User $user, float $openingFloat): CashRegisterSession
    {
        $existing = $this->getTodaySession($restaurant);

        if ($existing?->isOpen()) {
            throw new InvalidArgumentException('Today\'s register is already open.');
        }

        if ($existing?->closed_at !== null) {
            throw new InvalidArgumentException('Today\'s register has already been closed.');
        }

        return CashRegisterSession::create([
            'restaurant_id' => $restaurant->id,
            'opened_by' => $user->id,
            'session_date' => today(),
            'opening_float' => round($openingFloat, 2),
            'opened_at' => now(),
        ]);
    }

    /**
     * @return array{cash: float, upi: float, card: float, other: float, total: float}
     */
    public function summarizeTodayPayments(Restaurant $restaurant): array
    {
        $rows = Payment::query()
            ->where('restaurant_id', $restaurant->id)
            ->whereDate('created_at', today())
            ->selectRaw('method, SUM(amount) as total')
            ->groupBy('method')
            ->pluck('total', 'method');

        $cash = (float) ($rows[PaymentMethod::Cash->value] ?? 0);
        $upi = (float) ($rows[PaymentMethod::Upi->value] ?? 0);
        $card = (float) ($rows[PaymentMethod::Card->value] ?? 0);
        $other = (float) ($rows[PaymentMethod::Other->value] ?? 0);

        return [
            'cash' => round($cash, 2),
            'upi' => round($upi, 2),
            'card' => round($card, 2),
            'other' => round($other, 2),
            'total' => round($cash + $upi + $card + $other, 2),
        ];
    }

    public function closeSession(CashRegisterSession $session, User $user, float $actualCash, ?string $notes = null): CashRegisterSession
    {
        if (! $session->isOpen()) {
            throw new InvalidArgumentException('This register session is already closed.');
        }

        return DB::transaction(function () use ($session, $user, $actualCash, $notes) {
            $summary = $this->summarizeTodayPayments($session->restaurant);
            $expectedCash = round((float) $session->opening_float + $summary['cash'], 2);
            $actualCash = round($actualCash, 2);

            $session->update([
                'closed_by' => $user->id,
                'expected_cash' => $expectedCash,
                'actual_cash' => $actualCash,
                'cash_difference' => round($actualCash - $expectedCash, 2),
                'total_cash_sales' => $summary['cash'],
                'total_upi_sales' => $summary['upi'],
                'total_card_sales' => $summary['card'],
                'total_other_sales' => $summary['other'],
                'total_sales' => $summary['total'],
                'notes' => $notes,
                'closed_at' => now(),
            ]);

            return $session->fresh(['opener', 'closer']);
        });
    }
}
