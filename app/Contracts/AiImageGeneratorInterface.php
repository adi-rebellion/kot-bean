<?php

namespace App\Contracts;

use App\Models\Product;

interface AiImageGeneratorInterface
{
    /**
     * Generate a product image and return the stored file path.
     */
    public function generateProductImage(Product $product, string $prompt): string;
}
