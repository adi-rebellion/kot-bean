<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockDailyRecord extends Model
{
    protected $fillable = [
        'stock_item_id',
        'record_date',
        'opening_quantity',
        'restock_quantity',
        'closing_quantity',
        'used_quantity',
        'recorded_by',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'record_date' => 'date',
            'opening_quantity' => 'decimal:2',
            'restock_quantity' => 'decimal:2',
            'closing_quantity' => 'decimal:2',
            'used_quantity' => 'decimal:2',
        ];
    }

    public function stockItem(): BelongsTo
    {
        return $this->belongsTo(StockItem::class);
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}
