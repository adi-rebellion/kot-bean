<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;

class PwaController extends Controller
{
    public function manifest(): JsonResponse
    {
        $startUrl = url('/');

        return response()->json([
            'name' => config('app.name', 'KotBean'),
            'short_name' => 'KotBean',
            'description' => 'Restaurant POS, KOT, inventory, and billing',
            'start_url' => $startUrl,
            'scope' => url('/'),
            'display' => 'standalone',
            'orientation' => 'any',
            'background_color' => '#F8FAFC',
            'theme_color' => '#F59E0B',
            'categories' => ['business', 'food'],
            'icons' => [
                [
                    'src' => asset('icons/icon.svg'),
                    'sizes' => 'any',
                    'type' => 'image/svg+xml',
                    'purpose' => 'any',
                ],
                [
                    'src' => asset('icons/icon-maskable.svg'),
                    'sizes' => 'any',
                    'type' => 'image/svg+xml',
                    'purpose' => 'maskable',
                ],
            ],
        ])->header('Content-Type', 'application/manifest+json');
    }
}
