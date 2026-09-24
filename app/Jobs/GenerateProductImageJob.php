<?php

namespace App\Jobs;

use App\Contracts\AiImageGeneratorInterface;
use App\Enums\AiGenerationStatus;
use App\Models\AiImageGeneration;
use App\Models\User;
use App\Notifications\AiImageGeneratedNotification;
use App\Notifications\AiImageFailedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class GenerateProductImageJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public AiImageGeneration $generation,
    ) {}

    public function handle(AiImageGeneratorInterface $generator): void
    {
        $this->generation->update([
            'status' => AiGenerationStatus::Processing,
            'started_at' => now(),
        ]);

        try {
            $product = $this->generation->product;
            $imagePath = $generator->generateProductImage($product, $this->generation->prompt);

            $this->generation->update([
                'status' => AiGenerationStatus::Completed,
                'image_path' => $imagePath,
                'completed_at' => now(),
            ]);

            $this->notifyUser(new AiImageGeneratedNotification($this->generation->fresh()));
        } catch (\Throwable $e) {
            Log::error('AI image generation failed', [
                'generation_id' => $this->generation->id,
                'error' => $e->getMessage(),
            ]);

            $this->generation->update([
                'status' => AiGenerationStatus::Failed,
                'error_message' => $e->getMessage(),
                'completed_at' => now(),
            ]);

            $this->notifyUser(new AiImageFailedNotification($this->generation->fresh()));
        }
    }

    private function notifyUser(object $notification): void
    {
        try {
            if ($this->generation->requested_by) {
                User::find($this->generation->requested_by)?->notify($notification);
            }
        } catch (\Throwable $e) {
            Log::warning('Could not send AI image notification', ['error' => $e->getMessage()]);
        }
    }
}
