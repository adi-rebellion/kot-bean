<?php

namespace App\Console\Commands;

use App\Models\Restaurant;
use Database\Seeders\CafeVendorsSeeder;
use Illuminate\Console\Command;

class SeedCafeVendorsCommand extends Command
{
    protected $signature = 'vendors:seed {slug? : Restaurant slug}';

    protected $description = 'Seed default café suppliers (vendors) for one restaurant';

    public function handle(): int
    {
        $slug = $this->argument('slug') ?? config('seeding.restaurant_slug');

        if ($slug === null || $slug === '') {
            $this->warn('Pass a slug: php artisan vendors:seed mr-bean-coffee-co');
            $this->newLine();
            $this->line('Restaurants:');
            $this->table(
                ['ID', 'Name', 'Slug'],
                Restaurant::query()->orderBy('name')->get(['id', 'name', 'slug'])
                    ->map(fn (Restaurant $r) => [$r->id, $r->name, $r->slug])
                    ->all(),
            );

            return self::SUCCESS;
        }

        $restaurant = Restaurant::query()->where('slug', $slug)->first();

        if (! $restaurant) {
            $this->error("No restaurant with slug [{$slug}].");

            return self::FAILURE;
        }

        (new CafeVendorsSeeder)->run($restaurant);

        return self::SUCCESS;
    }
}