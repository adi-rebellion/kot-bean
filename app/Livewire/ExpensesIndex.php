<?php

namespace App\Livewire;

use App\Enums\ExpensePaymentStatus;
use App\Models\Expense;
use App\Services\ExpenseService;
use App\Services\VendorService;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.kotbean')]
#[Title('Expenses')]
class ExpensesIndex extends Component
{
    public bool $showForm = false;

    public ?int $editingId = null;

    public string $title = '';

    public string $category = '';

    public ?int $vendor_id = null;

    public string $amount = '';

    public string $payment_status = 'paid';

    public string $paid_amount = '';

    public string $expense_date = '';

    public string $due_date = '';

    public string $notes = '';

    public function mount(): void
    {
        $this->expense_date = now()->toDateString();
    }

    public function create(): void
    {
        $this->resetForm();
        $this->showForm = true;
    }

    public function edit(int $expenseId): void
    {
        $expense = Expense::findOrFail($expenseId);

        $this->editingId = $expense->id;
        $this->title = $expense->title;
        $this->category = $expense->category;
        $this->vendor_id = $expense->vendor_id;
        $this->amount = (string) $expense->amount;
        $this->payment_status = $expense->payment_status->value;
        $this->paid_amount = (string) $expense->paid_amount;
        $this->expense_date = $expense->expense_date->toDateString();
        $this->due_date = $expense->due_date?->toDateString() ?? '';
        $this->notes = $expense->notes ?? '';
        $this->showForm = true;
    }

    public function updatedVendorId(): void
    {
        if ($this->vendor_id && $this->payment_status === ExpensePaymentStatus::Paid->value && ! $this->editingId) {
            $this->payment_status = ExpensePaymentStatus::Pending->value;
        }

        if (! $this->vendor_id) {
            $this->payment_status = ExpensePaymentStatus::Paid->value;
            $this->due_date = '';
        }
    }

    public function save(): void
    {
        $restaurantId = auth()->user()->restaurant_id;

        $rules = [
            'title' => ['required', 'string', 'max:255'],
            'category' => ['required', 'string', 'max:100'],
            'vendor_id' => [
                'nullable',
                Rule::exists('vendors', 'id')->where(fn ($query) => $query->where('restaurant_id', $restaurantId)),
            ],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'payment_status' => ['required', Rule::enum(ExpensePaymentStatus::class)],
            'expense_date' => ['required', 'date'],
            'due_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
        ];

        if ($this->payment_status === ExpensePaymentStatus::Partial->value) {
            $rules['paid_amount'] = ['required', 'numeric', 'min:0.01', 'lt:amount'];
        }

        $data = $this->validate($rules);
        $data['vendor_id'] = $this->vendor_id;

        if ($this->payment_status === ExpensePaymentStatus::Partial->value) {
            $data['paid_amount'] = $this->paid_amount;
        }

        $data['due_date'] = $this->due_date !== '' ? $this->due_date : null;

        $service = app(ExpenseService::class);

        if ($this->editingId) {
            $expense = Expense::findOrFail($this->editingId);
            $service->update($expense, $data);
        } else {
            $service->create($data, auth()->user());
        }

        $this->resetForm();
        $this->showForm = false;
        session()->flash('success', 'Expense saved successfully.');
    }

    public function delete(int $expenseId): void
    {
        $expense = Expense::findOrFail($expenseId);
        app(ExpenseService::class)->delete($expense);
        session()->flash('success', 'Expense deleted.');
    }

    public function cancel(): void
    {
        $this->resetForm();
        $this->showForm = false;
    }

    private function resetForm(): void
    {
        $this->editingId = null;
        $this->title = '';
        $this->category = '';
        $this->vendor_id = null;
        $this->amount = '';
        $this->payment_status = ExpensePaymentStatus::Paid->value;
        $this->paid_amount = '';
        $this->expense_date = now()->toDateString();
        $this->due_date = '';
        $this->notes = '';
    }

    public function render(): View
    {
        return view('livewire.expenses-index', [
            'expenses' => app(ExpenseService::class)->listForRestaurant(auth()->user()->restaurant),
            'vendors' => app(VendorService::class)->listForRestaurant(auth()->user()->restaurant),
            'paymentStatuses' => ExpensePaymentStatus::cases(),
            'totalExpenses' => Expense::query()->sum('amount'),
        ]);
    }
}
