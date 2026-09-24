<?php

namespace App\Models;

use App\Models\Concerns\BelongsToRestaurant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CashRegisterSession extends Model
{
    use BelongsToRestaurant;

    protected $fillable = [
        'restaurant_id',
        'opened_by',
        'closed_by',
        'session_date',
        'opening_float',
        'expected_cash',
        'actual_cash',
        'cash_difference',
        'total_cash_sales',
        'total_upi_sales',
        'total_card_sales',
        'total_other_sales',
        'total_sales',
        'notes',
        'opened_at',
        'closed_at',
    ];

    protected function casts(): array
    {
        return [
            'session_date' => 'date',
            'opening_float' => 'decimal:2',
            'expected_cash' => 'decimal:2',
            'actual_cash' => 'decimal:2',
            'cash_difference' => 'decimal:2',
            'total_cash_sales' => 'decimal:2',
            'total_upi_sales' => 'decimal:2',
            'total_card_sales' => 'decimal:2',
            'total_other_sales' => 'decimal:2',
            'total_sales' => 'decimal:2',
            'opened_at' => 'datetime',
            'closed_at' => 'datetime',
        ];
    }

    public function opener(): BelongsTo
    {
        return $this->belongsTo(User::class, 'opened_by');
    }

    public function closer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by');
    }

    public function isOpen(): bool
    {
        return $this->closed_at === null;
    }
}
