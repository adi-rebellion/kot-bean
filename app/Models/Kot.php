<?php

namespace App\Models;

use App\Enums\KotStatus;
use App\Models\Concerns\BelongsToRestaurant;
use Database\Factories\KotFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Kot extends Model
{
    /** @use HasFactory<KotFactory> */
    use BelongsToRestaurant, HasFactory;

    protected $fillable = [
        'restaurant_id',
        'order_id',
        'kot_number',
        'status',
        'restaurant_table_id',
        'created_by',
        'notes',
        'accepted_at',
        'preparing_at',
        'ready_at',
        'served_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => KotStatus::class,
            'accepted_at' => 'datetime',
            'preparing_at' => 'datetime',
            'ready_at' => 'datetime',
            'served_at' => 'datetime',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function table(): BelongsTo
    {
        return $this->belongsTo(RestaurantTable::class, 'restaurant_table_id');
    }

    public function restaurantTable(): BelongsTo
    {
        return $this->table();
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(KotItem::class);
    }
}
