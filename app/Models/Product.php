<?php

namespace App\Models;

use App\Enums\StockStatus;
use App\Models\Concerns\BelongsToRestaurant;
use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class Product extends Model
{
    /** @use HasFactory<ProductFactory> */
    use BelongsToRestaurant, HasFactory, SoftDeletes;

    protected $fillable = [
        'restaurant_id',
        'category_id',
        'name',
        'slug',
        'description',
        'sku',
        'image_path',
        'price',
        'cost_price',
        'tax_rate',
        'stock',
        'min_stock',
        'preparation_time',
        'has_variants',
        'track_inventory',
        'is_available',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'cost_price' => 'decimal:2',
            'tax_rate' => 'decimal:2',
            'stock' => 'integer',
            'min_stock' => 'integer',
            'preparation_time' => 'integer',
            'has_variants' => 'boolean',
            'track_inventory' => 'boolean',
            'is_available' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class);
    }

    public function inventoryTransactions(): HasMany
    {
        return $this->hasMany(InventoryTransaction::class);
    }

    protected function stockStatus(): Attribute
    {
        return Attribute::get(fn (): StockStatus => StockStatus::fromStock($this->stock, $this->min_stock));
    }

    protected function effectivePrice(): Attribute
    {
        return Attribute::get(function (): string {
            if ($this->has_variants) {
                $variant = $this->variants()
                    ->where('is_default', true)
                    ->first()
                    ?? $this->variants()
                        ->where('is_available', true)
                        ->orderBy('sort_order')
                        ->first();

                return (string) ($variant?->price ?? $this->price);
            }

            return (string) $this->price;
        });
    }

    protected function imageUrl(): Attribute
    {
        return Attribute::get(fn (): ?string => $this->image_path
            ? Storage::url($this->image_path)
            : null);
    }

    public function isOrderable(): bool
    {
        if (! $this->is_available) {
            return false;
        }

        if (! $this->track_inventory) {
            return true;
        }

        if ($this->has_variants) {
            return $this->variants()
                ->where('is_available', true)
                ->where('stock', '>', 0)
                ->exists();
        }

        return $this->stock > 0;
    }
}
