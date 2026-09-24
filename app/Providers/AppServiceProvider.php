<?php

namespace App\Providers;

use App\Contracts\AiImageGeneratorInterface;
use App\Services\Ai\FakeImageGenerator;
use App\Services\Ai\OpenAiImageGenerator;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(AiImageGeneratorInterface::class, function ($app) {
            return match (config('ai.driver', 'fake')) {
                'openai' => $app->make(OpenAiImageGenerator::class),
                default => $app->make(FakeImageGenerator::class),
            };
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        config([
            'livewire.temporary_file_upload.rules' => ['required', 'file', 'max:8192'],
        ]);
    }
}
