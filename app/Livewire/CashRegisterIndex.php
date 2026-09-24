<?php

namespace App\Livewire;

use App\Models\CashRegisterSession;
use App\Services\CashRegisterService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.kotbean')]
#[Title('Cash Register')]
class CashRegisterIndex extends Component
{
    public string $openingFloat = '';

    public string $actualCash = '';

    public string $closeNotes = '';

    public function openRegister(): void
    {
        abort_unless(auth()->user()->hasPermission('payments.process'), 403);

        $data = $this->validate([
            'openingFloat' => ['required', 'numeric', 'min:0'],
        ]);

        try {
            app(CashRegisterService::class)->openSession(
                auth()->user()->restaurant,
                auth()->user(),
                (float) $data['openingFloat'],
            );

            $this->openingFloat = '';
            session()->flash('success', 'Cash register opened for today.');
        } catch (\InvalidArgumentException $exception) {
            session()->flash('error', $exception->getMessage());
        }
    }

    public function closeRegister(): void
    {
        abort_unless(auth()->user()->hasPermission('payments.process'), 403);

        $data = $this->validate([
            'actualCash' => ['required', 'numeric', 'min:0'],
            'closeNotes' => ['nullable', 'string'],
        ]);

        $session = app(CashRegisterService::class)->getTodaySession(auth()->user()->restaurant);

        if (! $session?->isOpen()) {
            session()->flash('error', 'No open register session found for today.');

            return;
        }

        try {
            app(CashRegisterService::class)->closeSession(
                $session,
                auth()->user(),
                (float) $data['actualCash'],
                $data['closeNotes'] ?: null,
            );

            $this->actualCash = '';
            $this->closeNotes = '';
            session()->flash('success', 'Cash register closed for today.');
        } catch (\InvalidArgumentException $exception) {
            session()->flash('error', $exception->getMessage());
        }
    }

    public function render(CashRegisterService $cashRegisterService): View
    {
        $restaurant = auth()->user()->restaurant;
        $todaySession = $cashRegisterService->getTodaySession($restaurant);
        $paymentSummary = $cashRegisterService->summarizeTodayPayments($restaurant);
        $expectedCash = $todaySession
            ? round((float) $todaySession->opening_float + $paymentSummary['cash'], 2)
            : null;

        return view('livewire.cash-register-index', [
            'todaySession' => $todaySession,
            'paymentSummary' => $paymentSummary,
            'expectedCash' => $expectedCash,
            'recentSessions' => CashRegisterSession::query()
                ->with(['opener', 'closer'])
                ->whereNotNull('closed_at')
                ->latest('session_date')
                ->limit(7)
                ->get(),
        ]);
    }
}
