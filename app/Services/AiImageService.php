<?php

namespace App\Services;

use App\Enums\AiGenerationStatus;
use App\Jobs\GenerateProductImageJob;
use App\Models\AiImageGeneration;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class AiImageService
{
    public function requestGeneration(Product $product, User $user): AiImageGeneration
    {
        $prompt = str_replace(
            ':product_name',
            $product->name,
            config('ai.default_prompt', 'Professional food photography of :product_name'),
        );

        if ($product->description) {
            $prompt .= '. '.$product->description;
        }

        $generation = AiImageGeneration::create([
            'restaurant_id' => $product->restaurant_id,
            'product_id' => $product->id,
            'provider' => config('ai.driver', 'fake'),
            'prompt' => $prompt,
            'status' => AiGenerationStatus::Queued,
            'requested_by' => $user->id,
        ]);

        if (config('queue.default') === 'sync') {
            GenerateProductImageJob::dispatchSync($generation);
        } else {
            GenerateProductImageJob::dispatch($generation);
        }

        return $generation->fresh();
    }

    public function applyGeneratedImage(AiImageGeneration $generation): void
    {
        DB::transaction(function () use ($generation) {
            $generation->loadMissing('product');

            if ($generation->status !== AiGenerationStatus::Completed || ! $generation->image_path) {
                throw new \InvalidArgumentException('Generation must be completed with an image path.');
            }

            $generation->product->update([
                'image_path' => $generation->image_path,
            ]);
        });
    }
}
