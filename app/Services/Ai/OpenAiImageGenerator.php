<?php

namespace App\Services\Ai;

use App\Contracts\AiImageGeneratorInterface;
use App\Models\Product;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class OpenAiImageGenerator implements AiImageGeneratorInterface
{
    public function generateProductImage(Product $product, string $prompt): string
    {
        $apiKey = config('ai.openai.api_key');

        if (empty($apiKey)) {
            throw new RuntimeException('OpenAI API key is not configured. Set OPENAI_API_KEY in your environment.');
        }

        $model = config('ai.openai.model', 'gpt-image-1');

        $payload = [
            'model' => $model,
            'prompt' => $prompt,
            'n' => 1,
        ];

        if ($model === 'gpt-image-1') {
            $payload['size'] = config('ai.openai.size', '1024x1024');
        } else {
            $payload['size'] = config('ai.openai.size', '1024x1024');
            $payload['response_format'] = 'b64_json';
        }

        $response = Http::withToken($apiKey)
            ->timeout(120)
            ->post('https://api.openai.com/v1/images/generations', $payload);

        if (! $response->successful()) {
            throw new RuntimeException($this->parseErrorMessage($response->json(), $response->body()));
        }

        $imageContents = $this->extractImageContents($response->json());

        if ($imageContents === null) {
            throw new RuntimeException('OpenAI did not return image data.');
        }

        $disk = config('ai.storage_disk', 'public');
        $filename = config('ai.storage_path', 'products/ai-generated')
            .'/'
            .$product->restaurant_id
            .'/'
            .$product->id
            .'-'
            .Str::uuid()
            .'.png';

        Storage::disk($disk)->put($filename, $imageContents);

        return $filename;
    }

    private function extractImageContents(array $data): ?string
    {
        $item = $data['data'][0] ?? null;

        if (! $item) {
            return null;
        }

        if (! empty($item['b64_json'])) {
            $decoded = base64_decode($item['b64_json'], true);

            return $decoded !== false ? $decoded : null;
        }

        if (! empty($item['url'])) {
            return Http::timeout(60)->get($item['url'])->body();
        }

        return null;
    }

    private function parseErrorMessage(?array $json, string $rawBody): string
    {
        $message = $json['error']['message'] ?? null;

        if (! $message) {
            return 'OpenAI image generation failed. Please try again.';
        }

        if (str_contains(strtolower($message), 'insufficient_quota') || str_contains(strtolower($message), 'no credits')) {
            return 'OpenAI account has no credits. Add billing at platform.openai.com/settings/billing';
        }

        if (str_contains(strtolower($message), 'does not exist')) {
            return 'Image model not available on your OpenAI account. Try setting OPENAI_IMAGE_MODEL=gpt-image-1 in .env';
        }

        return $message;
    }
}
