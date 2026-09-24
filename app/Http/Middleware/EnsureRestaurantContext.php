<?php

namespace App\Http\Middleware;

use App\Services\RestaurantWorkspaceService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureRestaurantContext
{
    public function __construct(private RestaurantWorkspaceService $workspaces) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user) {
            $this->workspaces->ensureCurrentWorkspace($user);
            $user->loadMissing(['restaurant', 'role.permissions', 'restaurants']);
        }

        return $next($request);
    }
}
