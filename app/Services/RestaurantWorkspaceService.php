<?php

namespace App\Services;

use App\Models\Permission;
use App\Models\Restaurant;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

class RestaurantWorkspaceService
{
    /**
     * @return array<string, list<string>|null>
     */
    public function systemRoleMatrix(): array
    {
        return [
            'owner' => null,
            'manager' => Permission::query()->whereNotIn('slug', ['staff.manage'])->pluck('slug')->all(),
            'cashier' => [
                'dashboard.view', 'pos.access', 'orders.view', 'orders.manage', 'menu.view',
                'inventory.view', 'tables.view', 'payments.process', 'customers.view', 'promotions.view',
            ],
            'waiter' => [
                'dashboard.view', 'pos.access', 'orders.view', 'orders.manage', 'menu.view',
                'inventory.view', 'tables.view', 'tables.manage', 'payments.process', 'customers.view',
            ],
            'kitchen' => ['orders.view', 'kitchen.access'],
        ];
    }

    public function createWorkspace(string $name, User $owner): Restaurant
    {
        return DB::transaction(function () use ($name, $owner) {
            $restaurant = Restaurant::create([
                'name' => $name,
                'slug' => $this->uniqueSlug($name),
                'currency' => 'INR',
                'timezone' => 'Asia/Kolkata',
                'default_tax_rate' => 5,
                'is_active' => true,
            ]);

            $roles = $this->provisionSystemRoles($restaurant);
            $this->attach($owner, $restaurant, $roles['owner']);
            $this->switchTo($owner, $restaurant);

            return $restaurant;
        });
    }

    /**
     * @return array<string, Role>
     */
    public function provisionSystemRoles(Restaurant $restaurant): array
    {
        $allSlugs = Permission::query()->pluck('slug')->all();
        $roles = [];

        foreach ($this->systemRoleMatrix() as $slug => $permissionSlugs) {
            $role = Role::query()->firstOrCreate(
                [
                    'restaurant_id' => $restaurant->id,
                    'slug' => $slug,
                ],
                [
                    'name' => ucfirst($slug),
                    'is_system' => true,
                ],
            );

            $ids = Permission::query()
                ->whereIn('slug', $permissionSlugs ?? $allSlugs)
                ->pluck('id');

            $role->permissions()->sync($ids);
            $roles[$slug] = $role;
        }

        return $roles;
    }

    public function attach(User $user, Restaurant $restaurant, Role $role): void
    {
        if ($role->restaurant_id !== $restaurant->id) {
            throw new InvalidArgumentException('Role does not belong to this business.');
        }

        $user->restaurants()->syncWithoutDetaching([
            $restaurant->id => ['role_id' => $role->id],
        ]);
    }

    public function switchTo(User $user, Restaurant $restaurant): void
    {
        $membership = $user->restaurants()
            ->where('restaurants.id', $restaurant->id)
            ->first();

        if (! $membership) {
            throw new InvalidArgumentException('You do not have access to this business.');
        }

        $user->update([
            'restaurant_id' => $restaurant->id,
            'role_id' => $membership->pivot->role_id,
        ]);

        $user->setRelation('restaurant', $restaurant);
        $user->unsetRelation('role');
        $user->load('role.permissions');
    }

    public function ensureCurrentWorkspace(User $user): void
    {
        $hasCurrent = $user->restaurant_id
            && $user->restaurants()->where('restaurants.id', $user->restaurant_id)->exists();

        if ($hasCurrent) {
            return;
        }

        $first = $user->restaurants()->orderBy('name')->first();

        if ($first) {
            $this->switchTo($user, $first);
        }
    }

    public function uniqueSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'business';
        $slug = $base;
        $suffix = 1;

        while (Restaurant::query()->where('slug', $slug)->exists()) {
            $slug = "{$base}-{$suffix}";
            $suffix++;
        }

        return $slug;
    }
}
