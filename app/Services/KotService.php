<?php

namespace App\Services;

use App\Enums\KotStatus;
use App\Models\Kot;
use App\Models\Restaurant;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class KotService
{
    public function updateStatus(Kot $kot, KotStatus $status): Kot
    {
        return DB::transaction(function () use ($kot, $status) {
            $attributes = ['status' => $status];

            match ($status) {
                KotStatus::Accepted => $attributes['accepted_at'] = now(),
                KotStatus::Preparing => $attributes['preparing_at'] = now(),
                KotStatus::Ready => $attributes['ready_at'] = now(),
                KotStatus::Served => $attributes['served_at'] = now(),
                default => null,
            };

            $kot->update($attributes);

            return $kot->fresh();
        });
    }

    public function getActiveKots(Restaurant $restaurant): Collection
    {
        return Kot::query()
            ->where('restaurant_id', $restaurant->id)
            ->whereIn('status', [
                KotStatus::Pending,
                KotStatus::Accepted,
                KotStatus::Preparing,
                KotStatus::Ready,
            ])
            ->with(['order', 'items', 'restaurantTable'])
            ->orderBy('created_at')
            ->get();
    }
}
