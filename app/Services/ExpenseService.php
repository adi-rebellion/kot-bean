<?php

namespace App\Services;

use App\Enums\ExpensePaymentStatus;
use App\Models\Expense;
use App\Models\Restaurant;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
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
                'paid_by_user_id' => $this->resolveStaffUserId($user->restaurant, $data['paid_by_user_id'] ?? null),
                'pending_on_user_id' => $this->resolveStaffUserId($user->restaurant, $data['pending_on_user_id'] ?? null),
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
                'paid_by_user_id' => array_key_exists('paid_by_user_id', $data)
                    ? $this->resolveStaffUserId($expense->restaurant, $data['paid_by_user_id'])
                    : $expense->paid_by_user_id,
                'pending_on_user_id' => array_key_exists('pending_on_user_id', $data)
                    ? $this->resolveStaffUserId($expense->restaurant, $data['pending_on_user_id'])
                    : $expense->pending_on_user_id,
            ]);

            return $expense->fresh(['vendor', 'paidBy', 'pendingOn']);
        });
    }

    public function delete(Expense $expense): void
    {
        DB::transaction(fn () => $expense->delete());
    }

    public function listForRestaurant(Restaurant $restaurant): Collection
    {
        return Expense::query()
            ->with(['vendor', 'paidBy', 'pendingOn'])
            ->where('restaurant_id', $restaurant->id)
            ->orderByDesc('expense_date')
            ->get();
    }

    public function staffForRestaurant(Restaurant $restaurant): Collection
    {
        return $restaurant->members()
            ->orderBy('name')
            ->get();
    }

    /**
     * @return array{total_paid_by: float, total_pending_on: float}
     */
    public function summarizeStaffLedger(
        Restaurant $restaurant,
        ?int $staffUserId = null,
        string $involvement = 'all',
        ?string $dateFrom = null,
        ?string $dateTo = null,
    ): array {
        $entries = $this->staffLedgerQuery($restaurant, $staffUserId, $involvement, $dateFrom, $dateTo)->get();

        $paidBy = $entries->whereNotNull('paid_by_user_id');
        $pendingOn = $entries->whereNotNull('pending_on_user_id');

        if ($staffUserId) {
            $paidBy = $paidBy->where('paid_by_user_id', $staffUserId);
            $pendingOn = $pendingOn->where('pending_on_user_id', $staffUserId);
        }

        return [
            'total_paid_by' => round((float) $paidBy->sum('amount'), 2),
            'total_pending_on' => round((float) $pendingOn->sum(function (Expense $expense): float {
                if ($expense->vendor_id && $expense->balance_due > 0) {
                    return $expense->balance_due;
                }

                if ($expense->vendor_id) {
                    return 0.0;
                }

                return (float) $expense->amount;
            }), 2),
        ];
    }

    public function staffLedgerEntries(
        Restaurant $restaurant,
        ?int $staffUserId = null,
        string $involvement = 'all',
        ?string $dateFrom = null,
        ?string $dateTo = null,
        int $perPage = 20,
    ): LengthAwarePaginator {
        return $this->staffLedgerQuery($restaurant, $staffUserId, $involvement, $dateFrom, $dateTo)
            ->with(['vendor', 'paidBy', 'pendingOn'])
            ->orderByDesc('expense_date')
            ->orderByDesc('id')
            ->paginate($perPage);
    }

    private function staffLedgerQuery(
        Restaurant $restaurant,
        ?int $staffUserId,
        string $involvement,
        ?string $dateFrom,
        ?string $dateTo,
    ) {
        $query = Expense::query()
            ->where('restaurant_id', $restaurant->id)
            ->where(function ($builder): void {
                $builder->whereNotNull('paid_by_user_id')
                    ->orWhereNotNull('pending_on_user_id');
            });

        if ($staffUserId) {
            $query->where(function ($builder) use ($staffUserId, $involvement): void {
                match ($involvement) {
                    'paid_by' => $builder->where('paid_by_user_id', $staffUserId),
                    'pending_on' => $builder->where('pending_on_user_id', $staffUserId),
                    default => $builder->where('paid_by_user_id', $staffUserId)
                        ->orWhere('pending_on_user_id', $staffUserId),
                };
            });
        } elseif ($involvement === 'paid_by') {
            $query->whereNotNull('paid_by_user_id');
        } elseif ($involvement === 'pending_on') {
            $query->whereNotNull('pending_on_user_id');
        }

        if ($dateFrom) {
            $query->whereDate('expense_date', '>=', $dateFrom);
        }

        if ($dateTo) {
            $query->whereDate('expense_date', '<=', $dateTo);
        }

        return $query;
    }

    private function resolveStaffUserId(Restaurant $restaurant, mixed $userId): ?int
    {
        if ($userId === null || $userId === '' || $userId === 0) {
            return null;
        }

        $userId = (int) $userId;

        $isMember = $restaurant->members()->where('users.id', $userId)->exists();

        if (! $isMember) {
            throw new InvalidArgumentException('Selected staff member is not part of this restaurant.');
        }

        return $userId;
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
