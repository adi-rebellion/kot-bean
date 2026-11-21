<?php

namespace Database\Seeders;

use App\Models\Restaurant;
use App\Models\Vendor;
use Illuminate\Database\Seeder;

class CafeVendorsSeeder extends Seeder
{
    /**
     * Default supplier list (Mr Bean Coffee Co. and similar cafés).
     *
     * @var array<int, array{name: string, phone?: string|null, notes: string}>
     */
    private const VENDORS = [
        [
            'name' => 'Vandana Business',
            'phone' => '919669645684',
            'notes' => "Supplies: Coffee and syrups\nPOC: Mandal Ji",
        ],
        [
            'name' => 'RR Enterprises',
            'phone' => null,
            'notes' => "Supplies: Chocolate and filling\nPOC: Ruchi",
        ],
        [
            'name' => 'Ashapura Supela',
            'phone' => '917828699299',
            'notes' => "Supplies: Sauces and condiments, milk\nLocation: Supela",
        ],
        [
            'name' => 'Ashapura Aadarsh Nagar',
            'phone' => '917974038836',
            'notes' => "Supplies: Sauces and condiments, milk\nLocation: Aadarsh Nagar",
        ],
        [
            'name' => 'Pio Ice Cubes',
            'phone' => '919826124196',
            'notes' => "Supplies: Ice cubes\nPOC: Lucky (+91 98261 24196), Akshit (+91 83057 26952), alternate +91 90987 68830",
        ],
    ];

    public function run(?Restaurant $restaurant = null): void
    {
        $restaurant ??= $this->resolveRestaurant();

        if (! $restaurant) {
            $this->command?->error('No restaurant found. Create a workspace first.');

            return;
        }

        foreach (self::VENDORS as $row) {
            Vendor::updateOrCreate(
                [
                    'restaurant_id' => $restaurant->id,
                    'name' => $row['name'],
                ],
                [
                    'phone' => $this->formatPhone($row['phone'] ?? null),
                    'notes' => $row['notes'],
                    'is_active' => true,
                ],
            );
        }

        $this->command?->info('Seeded '.count(self::VENDORS)." vendors for {$restaurant->name} ({$restaurant->slug}).");
    }

    private function resolveRestaurant(): ?Restaurant
    {
        $slug = config('seeding.restaurant_slug');

        if ($slug) {
            return Restaurant::query()->where('slug', $slug)->first();
        }

        return Restaurant::query()->where('slug', 'mr-bean-coffee-co')->first()
            ?? Restaurant::query()->where('slug', 'bean-brew-cafe')->first()
            ?? Restaurant::query()->orderBy('id')->first();
    }

    private function formatPhone(?string $digits): ?string
    {
        if ($digits === null || $digits === '') {
            return null;
        }

        $digits = preg_replace('/\D+/', '', $digits) ?? '';

        if ($digits === '') {
            return null;
        }

        if (strlen($digits) === 10) {
            $digits = '91'.$digits;
        }

        if (str_starts_with($digits, '91') && strlen($digits) === 12) {
            return '+91 '.substr($digits, 2, 5).' '.substr($digits, 7);
        }

        return '+'.$digits;
    }
}