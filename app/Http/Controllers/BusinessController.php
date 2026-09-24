<?php

namespace App\Http\Controllers;

use App\Models\Restaurant;
use App\Services\RestaurantWorkspaceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class BusinessController extends Controller
{
    public function store(Request $request, RestaurantWorkspaceService $workspaces): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        $restaurant = $workspaces->createWorkspace($data['name'], $request->user());

        return redirect()
            ->route('dashboard')
            ->with('success', "{$restaurant->name} is ready. You are now working in this business.");
    }

    public function switch(Request $request, RestaurantWorkspaceService $workspaces): RedirectResponse
    {
        $data = $request->validate([
            'restaurant_id' => ['required', 'integer', 'exists:restaurants,id'],
        ]);

        $restaurant = Restaurant::query()->findOrFail($data['restaurant_id']);

        try {
            $workspaces->switchTo($request->user(), $restaurant);
        } catch (\InvalidArgumentException $exception) {
            abort(404);
        }

        return redirect()
            ->route('dashboard')
            ->with('success', "Switched to {$restaurant->name}.");
    }
}
