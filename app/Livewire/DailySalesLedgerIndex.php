<?php

namespace App\Livewire;

use App\Models\DailySalesLedger;
use App\Services\DailySalesLedgerService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Carbon;
use InvalidArgumentException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.kotbean')]
#[Title('Daily Sales Ledger')]
class DailySalesLedgerIndex extends Component
{
    public string $saleDate = '';

    public string $cashAmount = '';

    public string $onlineAmount = '';

    public string $notes = '';

    public ?int $editingId = null;

    public function mount(): void
    {
        $this->saleDate = today()->toDateString();
    }

    public function edit(int $entryId): void
    {
        abort_unless($this->canManage(), 403);

        $entry = DailySalesLedger::findOrFail($entryId);

        $this->editingId = $entry->id;
        $this->saleDate = $entry->sale_date->toDateString();
        $this->cashAmount = (string) $entry->cash_amount;
        $this->onlineAmount = (string) $entry->online_amount;
        $this->notes = $entry->notes ?? '';
    }

    public function save(): void
    {
        abort_unless($this->canManage(), 403);

        $data = $this->validate([
            'saleDate' => ['required', 'date', 'before_or_equal:today'],
            'cashAmount' => ['required', 'numeric', 'min:0'],
            'onlineAmount' => ['required', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        try {
            app(DailySalesLedgerService::class)->upsert(
                auth()->user()->restaurant,
                Carbon::parse($data['saleDate']),
                (float) $data['cashAmount'],
                (float) $data['onlineAmount'],
                auth()->user(),
                $data['notes'] ?: null,
            );

            $this->resetForm();
            session()->flash('success', 'Daily sales saved.');
        } catch (InvalidArgumentException $exception) {
            session()->flash('error', $exception->getMessage());
        }
    }

    public function delete(int $entryId): void
    {
        abort_unless($this->canManage(), 403);

        $entry = DailySalesLedger::findOrFail($entryId);
        app(DailySalesLedgerService::class)->deleteEntry($entry);

        if ($this->editingId === $entryId) {
            $this->resetForm();
        }

        session()->flash('success', 'Entry removed.');
    }

    public function cancelEdit(): void
    {
        $this->resetForm();
    }

    private function resetForm(): void
    {
        $this->editingId = null;
        $this->saleDate = today()->toDateString();
        $this->cashAmount = '';
        $this->onlineAmount = '';
        $this->notes = '';
    }

    private function canManage(): bool
    {
        return auth()->user()->hasPermission('payments.process');
    }

    public function render(): View
    {
        $restaurant = auth()->user()->restaurant;
        $service = app(DailySalesLedgerService::class);
        $entries = $service->recentEntries($restaurant);

        $selectedDate = Carbon::parse($this->saleDate ?: today());
        $posTotals = $service->posTotalsForDate($restaurant, $selectedDate);

        $entryForSelectedDate = $entries->first(
            fn (DailySalesLedger $row) => $row->sale_date->toDateString() === $selectedDate->toDateString()
        );

        return view('livewire.daily-sales-ledger-index', [
            'entries' => $entries,
            'posTotals' => $posTotals,
            'entryForSelectedDate' => $entryForSelectedDate,
            'canManage' => $this->canManage(),
        ]);
    }
}