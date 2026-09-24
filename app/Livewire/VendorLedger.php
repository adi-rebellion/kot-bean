<?php

namespace App\Livewire;

use App\Enums\ExpensePaymentStatus;
use App\Models\Expense;
use App\Services\VendorService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.kotbean')]
#[Title('Vendor Ledger')]
class VendorLedger extends Component
{
    use WithPagination;

    #[Url]
    public ?int $vendorFilter = null;

    #[Url]
    public string $statusFilter = 'all';

    #[Url]
    public string $dateFrom = '';

    #[Url]
    public string $dateTo = '';

    public ?int $payingExpenseId = null;

    public string $paymentAmount = '';

    public function mount(): void
    {
        if ($this->dateFrom === '') {
            $this->dateFrom = now()->startOfMonth()->toDateString();
        }

        if ($this->dateTo === '') {
            $this->dateTo = now()->toDateString();
        }
    }

    public function updatedVendorFilter(): void
    {
        $this->resetPage();
    }

    public function updatedStatusFilter(): void
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

    public function openPayment(int $expenseId): void
    {
        abort_unless(auth()->user()->hasPermission('expenses.manage'), 403);

        $expense = Expense::with('vendor')->findOrFail($expenseId);
        $this->payingExpenseId = $expense->id;
        $this->paymentAmount = number_format($expense->balance_due, 2, '.', '');
    }

    public function recordPayment(): void
    {
        abort_unless(auth()->user()->hasPermission('expenses.manage'), 403);

        $this->validate([
            'paymentAmount' => ['required', 'numeric', 'min:0.01'],
        ]);

        $expense = Expense::findOrFail($this->payingExpenseId);
        app(VendorService::class)->recordPayment($expense, (float) $this->paymentAmount);

        $this->payingExpenseId = null;
        $this->paymentAmount = '';
        session()->flash('success', 'Payment recorded.');
    }

    public function markPaid(int $expenseId): void
    {
        abort_unless(auth()->user()->hasPermission('expenses.manage'), 403);

        $expense = Expense::findOrFail($expenseId);
        app(VendorService::class)->markPaid($expense);

        session()->flash('success', 'Expense marked as paid.');
    }

    public function cancelPayment(): void
    {
        $this->payingExpenseId = null;
        $this->paymentAmount = '';
    }

    public function render(): View
    {
        $restaurant = auth()->user()->restaurant;
        $vendorService = app(VendorService::class);

        return view('livewire.vendor-ledger', [
            'entries' => $vendorService->ledgerEntries(
                $restaurant,
                $this->vendorFilter,
                $this->statusFilter,
                $this->dateFrom,
                $this->dateTo,
            ),
            'summary' => $vendorService->summarizeLedger(
                $restaurant,
                $this->vendorFilter,
                $this->statusFilter,
                $this->dateFrom,
                $this->dateTo,
            ),
            'vendors' => $vendorService->listForRestaurant($restaurant),
            'statuses' => ExpensePaymentStatus::cases(),
        ]);
    }
}
