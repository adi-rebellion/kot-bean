<?php

namespace App\Livewire;

use App\Models\Expense;
use App\Services\ExpenseService;
use Illuminate\Contracts\View\View;
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

    public string $amount = '';

    public string $expense_date = '';

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
        $this->amount = (string) $expense->amount;
        $this->expense_date = $expense->expense_date->toDateString();
        $this->notes = $expense->notes ?? '';
        $this->showForm = true;
    }

    public function save(): void
    {
        $data = $this->validate([
            'title' => 'required|string|max:255',
            'category' => 'required|string|max:100',
            'amount' => 'required|numeric|min:0.01',
            'expense_date' => 'required|date',
            'notes' => 'nullable|string',
        ]);

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
        $this->amount = '';
        $this->expense_date = now()->toDateString();
        $this->notes = '';
    }

    public function render(): View
    {
        return view('livewire.expenses-index', [
            'expenses' => app(ExpenseService::class)->listForRestaurant(auth()->user()->restaurant),
            'totalExpenses' => Expense::query()->sum('amount'),
        ]);
    }
}
