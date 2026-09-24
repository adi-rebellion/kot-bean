<?php

namespace App\Exceptions;

use App\Models\Product;
use App\Models\ProductVariant;
use Exception;

class InsufficientStockException extends Exception
{
    public function __construct(
        public readonly Product $product,
        public readonly int $requested,
        public readonly int $available,
        public readonly ?ProductVariant $variant = null,
    ) {
        $name = $variant
            ? "{$product->name} ({$variant->name})"
            : $product->name;

        parent::__construct(
            "Insufficient stock for {$name}. Requested: {$requested}, Available: {$available}."
        );
    }
}
