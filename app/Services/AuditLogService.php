<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class AuditLogService
{
    public function log(
        string $action,
        Model $auditable,
        ?array $old = null,
        ?array $new = null,
        ?string $description = null,
    ): AuditLog {
        $restaurantId = $this->resolveRestaurantId($auditable);

        return AuditLog::create([
            'restaurant_id' => $restaurantId,
            'user_id' => Auth::id(),
            'action' => $action,
            'auditable_type' => $auditable->getMorphClass(),
            'auditable_id' => $auditable->getKey(),
            'old_values' => $old,
            'new_values' => $new,
            'description' => $description,
            'ip_address' => request()?->ip(),
        ]);
    }

    private function resolveRestaurantId(Model $auditable): ?int
    {
        if (isset($auditable->restaurant_id)) {
            return (int) $auditable->restaurant_id;
        }

        if (Auth::check() && Auth::user()->restaurant_id) {
            return (int) Auth::user()->restaurant_id;
        }

        return null;
    }
}
