<?php

namespace App\Models;

use App\Models\Concerns\BelongsToRestaurant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StockItem extends Model
{
    use BelongsToRestaurant;

    protected $fillable = [
        'restaurant_id',
        'name',
        'unit',
        'current_quantity',
        'min_quantity',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'current_quantity' => 'decimal:2',
            'min_quantity' => 'decimal:2',
            'sort_order' => 'integer',
        ];
    }

    public function dailyRecords(): HasMany
    {
        return $this->hasMany(StockDailyRecord::class)->orderByDesc('record_date');
    }

    public function vendorPrices(): HasMany
    {
        return $this->hasMany(StockItemVendorPrice::class);
    }
}
