<?php

namespace App\Services;

use App\Enums\ExpensePaymentStatus;
use App\Models\Expense;
use App\Models\Restaurant;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class ExpenseService
{
    public function create(array $data, User $user): Expense
    {
        return DB::transaction(function () use ($data, $user) {
            $payment = $this->resolvePaymentFields($data, $user->restaurant_id);

            return Expense::create([
                'restaurant_id' => $user->restaurant_id,
                'vendor_id' => $data['vendor_id'] ?? null,
                'title' => $data['title'],
                'category' => $data['category'],
                'amount' => round((float) $data['amount'], 2),
                'payment_status' => $payment['payment_status'],
                'paid_amount' => $payment['paid_amount'],
                'expense_date' => $data['expense_date'],
                'due_date' => $data['due_date'] ?? null,
                'paid_at' => $payment['paid_at'],
                'notes' => $data['notes'] ?? null,
                'created_by' => $user->id,
            ]);
        });
    }

    public function update(Expense $expense, array $data): Expense
    {
        return DB::transaction(function () use ($expense, $data) {
            $amount = isset($data['amount']) ? round((float) $data['amount'], 2) : (float) $expense->amount;
            $payment = $this->resolvePaymentFields(
                array_merge($data, ['amount' => $amount]),
                $expense->restaurant_id,
                $expense,
            );

            $expense->update([
                'vendor_id' => array_key_exists('vendor_id', $data) ? $data['vendor_id'] : $expense->vendor_id,
                'title' => $data['title'] ?? $expense->title,
                'category' => $data['category'] ?? $expense->category,
                'amount' => $amount,
                'payment_status' => $payment['payment_status'],
                'paid_amount' => $payment['paid_amount'],
                'expense_date' => $data['expense_date'] ?? $expense->expense_date,
                'due_date' => array_key_exists('due_date', $data) ? $data['due_date'] : $expense->due_date,
                'paid_at' => $payment['paid_at'],
                'notes' => $data['notes'] ?? $expense->notes,
            ]);

            return $expense->fresh(['vendor']);
        });
    }

    public function delete(Expense $expense): void
    {
        DB::transaction(fn () => $expense->delete());
    }

    public function listForRestaurant(Restaurant $restaurant): Collection
    {
        return Expense::query()
            ->with('vendor')
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

    /**
     * @return array{payment_status: ExpensePaymentStatus, paid_amount: float, paid_at: ?Carbon}
     */
    private function resolvePaymentFields(array $data, int $restaurantId, ?Expense $existing = null): array
    {
        $amount = round((float) $data['amount'], 2);
        $vendorId = $data['vendor_id'] ?? $existing?->vendor_id;

        if ($vendorId) {
            Vendor::query()
                ->where('restaurant_id', $restaurantId)
                ->findOrFail($vendorId);
        }

        $status = isset($data['payment_status'])
            ? ExpensePaymentStatus::from($data['payment_status'])
            : ($vendorId ? ExpensePaymentStatus::Pending : ExpensePaymentStatus::Paid);

        if (! $vendorId && $status !== ExpensePaymentStatus::Paid) {
            $status = ExpensePaymentStatus::Paid;
        }

        $paidAmount = match ($status) {
            ExpensePaymentStatus::Paid => $amount,
            ExpensePaymentStatus::Pending => 0.0,
            ExpensePaymentStatus::Partial => round((float) ($data['paid_amount'] ?? $existing?->paid_amount ?? 0), 2),
        };

        if ($status === ExpensePaymentStatus::Partial && ($paidAmount <= 0 || $paidAmount >= $amount)) {
            throw new InvalidArgumentException('Partial payment must be greater than zero and less than the expense amount.');
        }

        return [
            'payment_status' => $status,
            'paid_amount' => $paidAmount,
            'paid_at' => $status === ExpensePaymentStatus::Paid ? now() : null,
        ];
    }
}
