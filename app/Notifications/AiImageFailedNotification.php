<?php

namespace App\Notifications;

use App\Models\AiImageGeneration;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class AiImageFailedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public AiImageGeneration $generation,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'ai_image_failed',
            'message' => 'Failed to generate image for '.$this->generation->product->name,
            'product_id' => $this->generation->product_id,
            'generation_id' => $this->generation->id,
        ];
    }
}
