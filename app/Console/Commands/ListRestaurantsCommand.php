<?php

namespace App\Console\Commands;

use App\Models\Restaurant;
use Illuminate\Console\Command;

class ListRestaurantsCommand extends Command
{
    protected $signature = 'restaurants:list';

    protected $description = 'List all restaurants (id, name, slug) for seeding and configuration';

    public function handle(): int
    {
        $rows = Restaurant::query()
            ->orderBy('id')
            ->get(['id', 'name', 'slug', 'is_active']);

        if ($rows->isEmpty()) {
            $this->warn('No restaurants in the database.');

            return self::SUCCESS;
        }

        $this->table(
            ['ID', 'Name', 'Slug', 'Active'],
            $rows->map(fn (Restaurant $r) => [
                $r->id,
                $r->name,
                $r->slug,
                $r->is_active ? 'yes' : 'no',
            ])->all(),
        );

        $this->newLine();
        $this->line('Seed menu for one: php artisan menu:seed <slug>');
        $this->line('Or set in .env: SEED_RESTAURANT_SLUG=<slug>');

        return self::SUCCESS;
    }
}