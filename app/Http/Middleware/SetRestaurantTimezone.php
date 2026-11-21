<?php

namespace App\Http\Middleware;

use App\Support\Timezones;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

class SetRestaurantTimezone
{
    public function handle(Request $request, Closure $next): Response
    {
        $timezone = Timezones::resolve($request->user()?->restaurant?->timezone);

        config(['app.timezone' => $timezone]);
        date_default_timezone_set($timezone);

        View::share([
            'restaurantTimezone' => $timezone,
            'restaurantTimezoneLabel' => Timezones::labelFor($timezone),
            'restaurantTimezoneAbbrev' => Timezones::abbreviation($timezone),
            'restaurantLocalTime' => now()->format('g:i A'),
        ]);

        return $next($request);
    }
}