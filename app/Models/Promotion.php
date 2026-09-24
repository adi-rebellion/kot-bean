<?php

namespace App\Models;

use App\Enums\PromotionRule;
use App\Enums\PromotionType;
use App\Models\Concerns\BelongsToRestaurant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Promotion extends Model
{
    use BelongsToRestaurant;

    protected $fillable = [
        'restaurant_id',
        'name',
        'code',
        'type',
        'value',
        'auto_apply',
        'expires_at',
        'max_uses',
        'uses_count',
        'rule',
        'min_order_amount',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'type' => PromotionType::class,
            'rule' => PromotionRule::class,
            'value' => 'decimal:2',
            'min_order_amount' => 'decimal:2',
            'auto_apply' => 'boolean',
            'is_active' => 'boolean',
            'expires_at' => 'datetime',
            'max_uses' => 'integer',
            'uses_count' => 'integer',
        ];
    }

    public function redemptions(): HasMany
    {
        return $this->hasMany(PromotionRedemption::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    public function hasUsesRemaining(): bool
    {
        return $this->max_uses === null || $this->uses_count < $this->max_uses;
    }

    public function formattedValue(): string
    {
        return match ($this->type) {
            PromotionType::Percentage => rtrim(rtrim(number_format((float) $this->value, 2), '0'), '.').'%',
            PromotionType::Fixed => '₹'.number_format((float) $this->value, 2),
        };
    }
}
