<?php

namespace App\Models;

use App\Models\Concerns\BelongsToRestaurant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DailySalesLedger extends Model
{
    use BelongsToRestaurant;

    protected $fillable = [
        'restaurant_id',
        'sale_date',
        'cash_amount',
        'online_amount',
        'notes',
        'recorded_by',
    ];

    protected function casts(): array
    {
        return [
            'sale_date' => 'date',
            'cash_amount' => 'decimal:2',
            'online_amount' => 'decimal:2',
        ];
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function totalAmount(): float
    {
        return round((float) $this->cash_amount + (float) $this->online_amount, 2);
    }
}