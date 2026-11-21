<?php

namespace App\Console\Commands;

use App\Models\Restaurant;
use Database\Seeders\CafeMenuSeeder;
use Illuminate\Console\Command;

class SeedCafeMenuCommand extends Command
{
    protected $signature = 'menu:seed {slug? : Restaurant slug (see restaurants table)}';

    protected $description = 'Seed the café menu (categories, products, customizations) for one restaurant';

    public function handle(): int
    {
        $slug = $this->argument('slug') ?? config('seeding.restaurant_slug');

        if ($slug === null || $slug === '') {
            $this->warn('No slug provided. Set SEED_RESTAURANT_SLUG in .env or pass: php artisan menu:seed your-restaurant-slug');
            $this->newLine();
            $this->line('Restaurants in database:');

            $rows = Restaurant::query()
                ->orderBy('name')
                ->get(['id', 'name', 'slug']);

            if ($rows->isEmpty()) {
                $this->error('No restaurants found. Create a workspace / sign up first.');

                return self::FAILURE;
            }

            $this->table(['ID', 'Name', 'Slug'], $rows->map(fn (Restaurant $r) => [$r->id, $r->name, $r->slug])->all());

            return self::SUCCESS;
        }

        $restaurant = Restaurant::query()->where('slug', $slug)->first();

        if (! $restaurant) {
            $this->error("No restaurant with slug [{$slug}]. Run php artisan menu:seed without arguments to list slugs.");

            return self::FAILURE;
        }

        $this->info("Seeding menu for {$restaurant->name} ({$restaurant->slug})…");

        (new CafeMenuSeeder)->run($restaurant);

        $this->info('Done.');

        return self::SUCCESS;
    }
}