<?php

namespace App\Models;

use App\Enums\AiGenerationStatus;
use App\Models\Concerns\BelongsToRestaurant;
use Database\Factories\AiImageGenerationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiImageGeneration extends Model
{
    /** @use HasFactory<AiImageGenerationFactory> */
    use BelongsToRestaurant, HasFactory;

    protected $fillable = [
        'restaurant_id',
        'product_id',
        'provider',
        'prompt',
        'status',
        'requested_by',
        'image_path',
        'error_message',
        'started_at',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => AiGenerationStatus::class,
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }
}
