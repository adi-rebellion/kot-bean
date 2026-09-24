<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\RestaurantWorkspaceService;
use Illuminate\Console\Command;

class RepairUserWorkspaces extends Command
{
    protected $signature = 'kotbean:repair-workspaces';

    protected $description = 'Provision or repair restaurant memberships and owner roles for users missing access';

    public function handle(RestaurantWorkspaceService $workspaces): int
    {
        foreach (User::query()->get() as $user) {
            if (! $user->restaurant_id || ! $user->restaurant) {
                $name = filled($user->name) ? "{$user->name}'s Café" : 'My Café';
                $workspaces->createWorkspace($name, $user);
                $user->refresh();
                $this->line("provisioned workspace for {$user->email}");
            } else {
                $restaurant = $user->restaurant;
                $roles = $workspaces->provisionSystemRoles($restaurant);
                $role = $user->role ?: $roles['owner'];
                $workspaces->attach($user, $restaurant, $role);

                if (! $user->role_id) {
                    $workspaces->switchTo($user, $restaurant);
                }

                $user->refresh();
                $this->line("repaired {$user->email} restaurant={$user->restaurant_id} role={$user->role_id}");
            }

            $user->load('role.permissions');
            $this->line('  dashboard.view='.($user->hasPermission('dashboard.view') ? 'yes' : 'no'));
        }

        return self::SUCCESS;
    }
}
