<?php

namespace App\Livewire;

use App\Services\ExpenseService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.kotbean')]
#[Title('Staff Expense Ledger')]
class StaffExpenseLedger extends Component
{
    use WithPagination;

    #[Url]
    public ?int $staffFilter = null;

    /** @var ''|'all'|'paid_by'|'pending_on' */
    #[Url]
    public string $involvementFilter = 'all';

    #[Url]
    public string $dateFrom = '';

    #[Url]
    public string $dateTo = '';

    public function mount(): void
    {
        if ($this->dateFrom === '') {
            $this->dateFrom = now()->startOfMonth()->toDateString();
        }

        if ($this->dateTo === '') {
            $this->dateTo = now()->toDateString();
        }
    }

    public function updatedStaffFilter(): void
    {
        $this->resetPage();
    }

    public function updatedInvolvementFilter(): void
    {
        $this->resetPage();
    }

    public function updatedDateFrom(): void
    {
        $this->resetPage();
    }

    public function updatedDateTo(): void
    {
        $this->resetPage();
    }

    public function render(): View
    {
        $restaurant = auth()->user()->restaurant;
        $expenseService = app(ExpenseService::class);

        return view('livewire.staff-expense-ledger', [
            'entries' => $expenseService->staffLedgerEntries(
                $restaurant,
                $this->staffFilter,
                $this->involvementFilter === '' ? 'all' : $this->involvementFilter,
                $this->dateFrom,
                $this->dateTo,
            ),
            'summary' => $expenseService->summarizeStaffLedger(
                $restaurant,
                $this->staffFilter,
                $this->involvementFilter === '' ? 'all' : $this->involvementFilter,
                $this->dateFrom,
                $this->dateTo,
            ),
            'staffMembers' => $expenseService->staffForRestaurant($restaurant),
        ]);
    }
}