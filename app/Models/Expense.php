<?php

namespace App\Models;

use App\Enums\ExpensePaymentStatus;
use App\Models\Concerns\BelongsToRestaurant;
use Database\Factories\ExpenseFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Expense extends Model
{
    /** @use HasFactory<ExpenseFactory> */
    use BelongsToRestaurant, HasFactory;

    protected $fillable = [
        'restaurant_id',
        'vendor_id',
        'title',
        'category',
        'amount',
        'payment_status',
        'paid_amount',
        'expense_date',
        'due_date',
        'paid_at',
        'notes',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'paid_amount' => 'decimal:2',
            'expense_date' => 'date',
            'due_date' => 'date',
            'paid_at' => 'datetime',
            'payment_status' => ExpensePaymentStatus::class,
        ];
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    protected function balanceDue(): Attribute
    {
        return Attribute::get(fn (): float => round(max(0, (float) $this->amount - (float) $this->paid_amount), 2));
    }
}
