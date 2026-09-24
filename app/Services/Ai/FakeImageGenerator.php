<?php

namespace App\Services\Ai;

use App\Contracts\AiImageGeneratorInterface;
use App\Models\Product;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class FakeImageGenerator implements AiImageGeneratorInterface
{
    public function generateProductImage(Product $product, string $prompt): string
    {
        $disk = config('ai.storage_disk', 'public');
        $directory = config('ai.storage_path', 'products/ai-generated')
            .'/'
            .$product->restaurant_id;

        Storage::disk($disk)->makeDirectory($directory);

        $filename = $directory.'/'.$product->id.'-'.Str::uuid().'.svg';
        $label = htmlspecialchars(Str::limit($product->name, 32, ''), ENT_QUOTES);

        $svg = <<<SVG
        <svg xmlns="http://www.w3.org/2000/svg" width="512" height="512" viewBox="0 0 512 512">
            <defs>
                <linearGradient id="g" x1="0%" y1="0%" x2="100%" y2="100%">
                    <stop offset="0%" style="stop-color:#fbbf24"/>
                    <stop offset="100%" style="stop-color:#f97316"/>
                </linearGradient>
            </defs>
            <rect width="512" height="512" fill="url(#g)"/>
            <text x="256" y="256" text-anchor="middle" dominant-baseline="middle" fill="white" font-family="system-ui,sans-serif" font-size="28" font-weight="600">{$label}</text>
        </svg>
        SVG;

        Storage::disk($disk)->put($filename, $svg);

        return $filename;
    }
}
