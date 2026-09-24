<?php

namespace App\Services;

use App\Enums\ExpensePaymentStatus;
use App\Models\Expense;
use App\Models\Restaurant;
use App\Models\Vendor;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class VendorService
{
    public function create(array $data, Restaurant $restaurant): Vendor
    {
        return Vendor::create([
            'restaurant_id' => $restaurant->id,
            'name' => $data['name'],
            'phone' => $data['phone'] ?? null,
            'email' => $data['email'] ?? null,
            'address' => $data['address'] ?? null,
            'notes' => $data['notes'] ?? null,
            'is_active' => $data['is_active'] ?? true,
        ]);
    }

    public function update(Vendor $vendor, array $data): Vendor
    {
        $vendor->update([
            'name' => $data['name'] ?? $vendor->name,
            'phone' => $data['phone'] ?? $vendor->phone,
            'email' => $data['email'] ?? $vendor->email,
            'address' => $data['address'] ?? $vendor->address,
            'notes' => $data['notes'] ?? $vendor->notes,
            'is_active' => $data['is_active'] ?? $vendor->is_active,
        ]);

        return $vendor->fresh();
    }

    public function delete(Vendor $vendor): void
    {
        DB::transaction(function () use ($vendor): void {
            Expense::query()
                ->where('vendor_id', $vendor->id)
                ->update(['vendor_id' => null]);

            $vendor->delete();
        });
    }

    public function listForRestaurant(Restaurant $restaurant): Collection
    {
        return Vendor::query()
            ->where('restaurant_id', $restaurant->id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
    }

    /**
     * @return array{total_billed: float, total_paid: float, total_pending: float}
     */
    public function summarizeLedger(Restaurant $restaurant, ?int $vendorId = null, ?string $status = null, ?string $dateFrom = null, ?string $dateTo = null): array
    {
        $query = $this->ledgerQuery($restaurant, $vendorId, $status, $dateFrom, $dateTo);

        $totalBilled = (float) (clone $query)->sum('amount');
        $totalPaid = (float) (clone $query)->sum('paid_amount');

        return [
            'total_billed' => round($totalBilled, 2),
            'total_paid' => round($totalPaid, 2),
            'total_pending' => round(max(0, $totalBilled - $totalPaid), 2),
        ];
    }

    public function ledgerEntries(
        Restaurant $restaurant,
        ?int $vendorId = null,
        ?string $status = null,
        ?string $dateFrom = null,
        ?string $dateTo = null,
        int $perPage = 20,
    ): LengthAwarePaginator {
        return $this->ledgerQuery($restaurant, $vendorId, $status, $dateFrom, $dateTo)
            ->with('vendor')
            ->orderByDesc('expense_date')
            ->orderByDesc('id')
            ->paginate($perPage);
    }

    public function recordPayment(Expense $expense, float $amount): Expense
    {
        return DB::transaction(function () use ($expense, $amount) {
            $amount = round($amount, 2);
            $newPaidAmount = round((float) $expense->paid_amount + $amount, 2);
            $total = (float) $expense->amount;

            if ($newPaidAmount > $total) {
                $newPaidAmount = $total;
            }

            $status = match (true) {
                $newPaidAmount <= 0 => ExpensePaymentStatus::Pending,
                $newPaidAmount < $total => ExpensePaymentStatus::Partial,
                default => ExpensePaymentStatus::Paid,
            };

            $expense->update([
                'paid_amount' => $newPaidAmount,
                'payment_status' => $status,
                'paid_at' => $status === ExpensePaymentStatus::Paid ? now() : $expense->paid_at,
            ]);

            return $expense->fresh(['vendor']);
        });
    }

    public function markPaid(Expense $expense): Expense
    {
        return DB::transaction(function () use ($expense) {
            $expense->update([
                'paid_amount' => $expense->amount,
                'payment_status' => ExpensePaymentStatus::Paid,
                'paid_at' => now(),
            ]);

            return $expense->fresh(['vendor']);
        });
    }

    private function ledgerQuery(
        Restaurant $restaurant,
        ?int $vendorId,
        ?string $status,
        ?string $dateFrom,
        ?string $dateTo,
    ) {
        $query = Expense::query()
            ->where('restaurant_id', $restaurant->id)
            ->whereNotNull('vendor_id');

        if ($vendorId) {
            $query->where('vendor_id', $vendorId);
        }

        if ($status && $status !== 'all') {
            $query->where('payment_status', $status);
        }

        if ($dateFrom) {
            $query->whereDate('expense_date', '>=', $dateFrom);
        }

        if ($dateTo) {
            $query->whereDate('expense_date', '<=', $dateTo);
        }

        return $query;
    }
}
