<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection;

#[Fillable(['name', 'email', 'password', 'restaurant_id', 'role_id', 'phone', 'is_active'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant::class);
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function restaurants(): BelongsToMany
    {
        return $this->belongsToMany(Restaurant::class)
            ->withPivot('role_id')
            ->withTimestamps();
    }

    /**
     * @return Collection<int, Restaurant>
     */
    public function accessibleRestaurants(): Collection
    {
        if ($this->relationLoaded('restaurants')) {
            return $this->restaurants->sortBy('name')->values();
        }

        return $this->restaurants()->orderBy('name')->get();
    }

    public function belongsToRestaurant(int $restaurantId): bool
    {
        return $this->restaurants()->where('restaurants.id', $restaurantId)->exists();
    }

    public function hasPermission(string $slug): bool
    {
        return $this->role?->hasPermission($slug) ?? false;
    }

    public function isOwner(): bool
    {
        return $this->role?->slug === 'owner';
    }
}
