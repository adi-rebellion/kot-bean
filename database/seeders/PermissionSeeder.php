<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            ['slug' => 'dashboard.view', 'name' => 'View Dashboard', 'group' => 'dashboard'],
            ['slug' => 'pos.access', 'name' => 'Access POS', 'group' => 'pos'],
            ['slug' => 'orders.view', 'name' => 'View Orders', 'group' => 'orders'],
            ['slug' => 'orders.manage', 'name' => 'Manage Orders', 'group' => 'orders'],
            ['slug' => 'kitchen.access', 'name' => 'Access Kitchen', 'group' => 'kitchen'],
            ['slug' => 'tables.view', 'name' => 'View Tables', 'group' => 'tables'],
            ['slug' => 'tables.manage', 'name' => 'Manage Tables', 'group' => 'tables'],
            ['slug' => 'menu.view', 'name' => 'View Menu', 'group' => 'menu'],
            ['slug' => 'menu.manage', 'name' => 'Manage Menu', 'group' => 'menu'],
            ['slug' => 'inventory.view', 'name' => 'View Inventory', 'group' => 'inventory'],
            ['slug' => 'inventory.manage', 'name' => 'Manage Inventory', 'group' => 'inventory'],
            ['slug' => 'customers.view', 'name' => 'View Customers', 'group' => 'customers'],
            ['slug' => 'customers.manage', 'name' => 'Manage Customers', 'group' => 'customers'],
            ['slug' => 'promotions.view', 'name' => 'View Promotions', 'group' => 'promotions'],
            ['slug' => 'promotions.manage', 'name' => 'Manage Promotions', 'group' => 'promotions'],
            ['slug' => 'reports.view', 'name' => 'View Reports', 'group' => 'reports'],
            ['slug' => 'revenue.view', 'name' => 'View Revenue', 'group' => 'reports'],
            ['slug' => 'staff.manage', 'name' => 'Manage Staff', 'group' => 'staff'],
            ['slug' => 'settings.manage', 'name' => 'Manage Settings', 'group' => 'settings'],
            ['slug' => 'payments.process', 'name' => 'Process Payments', 'group' => 'payments'],
            ['slug' => 'expenses.view', 'name' => 'View Expenses', 'group' => 'expenses'],
            ['slug' => 'expenses.manage', 'name' => 'Manage Expenses', 'group' => 'expenses'],
        ];

        foreach ($permissions as $permission) {
            Permission::updateOrCreate(['slug' => $permission['slug']], $permission);
        }

        $this->syncSystemRolePermissions();
    }

    private function syncSystemRolePermissions(): void
    {
        $allPermissionIds = Permission::query()->pluck('id');
        $managerPermissionIds = Permission::query()
            ->whereNotIn('slug', ['staff.manage'])
            ->pluck('id');
        $cashierPermissionSlugs = [
            'dashboard.view', 'pos.access', 'orders.view', 'orders.manage', 'menu.view',
            'inventory.view', 'tables.view', 'payments.process', 'customers.view', 'promotions.view',
        ];
        $cashierPermissionIds = Permission::query()
            ->whereIn('slug', $cashierPermissionSlugs)
            ->pluck('id');
        $kitchenPermissionIds = Permission::query()
            ->whereIn('slug', ['orders.view', 'kitchen.access'])
            ->pluck('id');

        Role::query()->where('slug', 'owner')->each(
            fn (Role $role) => $role->permissions()->sync($allPermissionIds)
        );

        Role::query()->where('slug', 'manager')->each(
            fn (Role $role) => $role->permissions()->sync($managerPermissionIds)
        );

        Role::query()->whereIn('slug', ['cashier', 'waiter'])->each(
            fn (Role $role) => $role->permissions()->sync($cashierPermissionIds)
        );

        Role::query()->where('slug', 'kitchen')->each(
            fn (Role $role) => $role->permissions()->sync($kitchenPermissionIds)
        );
    }
}
