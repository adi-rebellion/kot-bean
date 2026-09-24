<?php

return [

    /*
    |--------------------------------------------------------------------------
    | AI Image Generation Driver
    |--------------------------------------------------------------------------
    |
    | Supported: "openai", "fake"
    |
    */

    'driver' => env('AI_IMAGE_DRIVER', 'fake'),

    'openai' => [
        'api_key' => env('OPENAI_API_KEY'),
        'model' => env('OPENAI_IMAGE_MODEL', 'gpt-image-1'),
        'size' => env('OPENAI_IMAGE_SIZE', '1024x1024'),
    ],

    'default_prompt' => 'Professional studio food photography of :product_name, appetizing, well-lit, on a clean background',

    'storage_disk' => env('AI_IMAGE_STORAGE_DISK', 'public'),

    'storage_path' => 'products/ai-generated',

];
