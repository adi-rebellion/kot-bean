<?php

namespace App\Models;

use App\Models\Concerns\BelongsToRestaurant;
use Database\Factories\CustomerFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Customer extends Model
{
    /** @use HasFactory<CustomerFactory> */
    use BelongsToRestaurant, HasFactory;

    protected $fillable = [
        'restaurant_id',
        'name',
        'phone',
        'email',
        'address',
        'notes',
        'total_orders',
        'total_spent',
        'last_order_at',
    ];

    protected function casts(): array
    {
        return [
            'total_orders' => 'integer',
            'total_spent' => 'decimal:2',
            'last_order_at' => 'datetime',
        ];
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function latestOrder(): HasOne
    {
        return $this->hasOne(Order::class)->latestOfMany();
    }
}
