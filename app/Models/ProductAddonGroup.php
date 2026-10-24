<?php

namespace App\Models;

use App\Enums\AddonSelectionType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductAddonGroup extends Model
{
    protected $fillable = [
        'product_id',
        'name',
        'selection_type',
        'is_required',
        'max_selections',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'selection_type' => AddonSelectionType::class,
            'is_required' => 'boolean',
            'max_selections' => 'integer',
            'sort_order' => 'integer',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function options(): HasMany
    {
        return $this->hasMany(ProductAddonOption::class)->orderBy('sort_order')->orderBy('name');
    }

    public function availableOptions(): HasMany
    {
        return $this->options()->where('is_available', true);
    }
}
