<?php

namespace App\Services;

use App\Models\Expense;
use App\Models\Restaurant;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class ExpenseService
{
    public function create(array $data, User $user): Expense
    {
        return DB::transaction(function () use ($data, $user) {
            return Expense::create([
                'restaurant_id' => $user->restaurant_id,
                'title' => $data['title'],
                'category' => $data['category'],
                'amount' => round((float) $data['amount'], 2),
                'expense_date' => $data['expense_date'],
                'notes' => $data['notes'] ?? null,
                'created_by' => $user->id,
            ]);
        });
    }

    public function update(Expense $expense, array $data): Expense
    {
        return DB::transaction(function () use ($expense, $data) {
            $expense->update([
                'title' => $data['title'] ?? $expense->title,
                'category' => $data['category'] ?? $expense->category,
                'amount' => isset($data['amount']) ? round((float) $data['amount'], 2) : $expense->amount,
                'expense_date' => $data['expense_date'] ?? $expense->expense_date,
                'notes' => $data['notes'] ?? $expense->notes,
            ]);

            return $expense->fresh();
        });
    }

    public function delete(Expense $expense): void
    {
        DB::transaction(fn () => $expense->delete());
    }

    public function listForRestaurant(Restaurant $restaurant): Collection
    {
        return Expense::query()
            ->where('restaurant_id', $restaurant->id)
            ->orderByDesc('expense_date')
            ->get();
    }

    public function find(int $id, Restaurant $restaurant): ?Expense
    {
        return Expense::query()
            ->where('restaurant_id', $restaurant->id)
            ->find($id);
    }
}
