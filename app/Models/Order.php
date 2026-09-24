<?php

namespace App\Models;

use App\Enums\OrderStatus;
use App\Enums\OrderType;
use App\Models\Concerns\BelongsToRestaurant;
use Database\Factories\OrderFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Order extends Model
{
    /** @use HasFactory<OrderFactory> */
    use BelongsToRestaurant, HasFactory, SoftDeletes;

    protected $fillable = [
        'restaurant_id',
        'order_number',
        'restaurant_table_id',
        'customer_id',
        'created_by',
        'served_by',
        'type',
        'status',
        'subtotal',
        'tax_amount',
        'discount_amount',
        'promotion_id',
        'total',
        'notes',
        'delivery_address',
        'delivery_phone',
        'confirmed_at',
        'completed_at',
        'cancelled_at',
    ];

    protected function casts(): array
    {
        return [
            'type' => OrderType::class,
            'status' => OrderStatus::class,
            'subtotal' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'total' => 'decimal:2',
            'confirmed_at' => 'datetime',
            'completed_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    public function table(): BelongsTo
    {
        return $this->belongsTo(RestaurantTable::class, 'restaurant_table_id');
    }

    public function restaurantTable(): BelongsTo
    {
        return $this->table();
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function promotion(): BelongsTo
    {
        return $this->belongsTo(Promotion::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function createdBy(): BelongsTo
    {
        return $this->creator();
    }

    public function server(): BelongsTo
    {
        return $this->belongsTo(User::class, 'served_by');
    }

    public function servedBy(): BelongsTo
    {
        return $this->server();
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function kots(): HasMany
    {
        return $this->hasMany(Kot::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function inventoryTransactions(): HasMany
    {
        return $this->hasMany(InventoryTransaction::class);
    }

    public function recalculateTotals(): self
    {
        $this->loadMissing('items');

        $subtotal = $this->items->sum(
            fn (OrderItem $item): float => ($item->unit_price * $item->quantity) - $item->discount_amount
        );

        $taxAmount = $this->items->sum('tax_amount');

        $this->update([
            'subtotal' => $subtotal,
            'tax_amount' => $taxAmount,
            'total' => $subtotal + $taxAmount - $this->discount_amount,
        ]);

        return $this->refresh();
    }

    public static function generateOrderNumber(int $restaurantId): string
    {
        $date = now()->format('Ymd');

        $count = static::withoutGlobalScopes()
            ->where('restaurant_id', $restaurantId)
            ->whereDate('created_at', today())
            ->count();

        return sprintf('ORD-%s-%04d', $date, $count + 1);
    }
}
