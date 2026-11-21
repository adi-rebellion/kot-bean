<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductAddon;
use App\Models\Restaurant;
use Database\Seeders\CafeMenuSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CafeMenuSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeds_categories_products_and_flavour_addons(): void
    {
        $restaurant = Restaurant::create([
            'name' => 'Seed Test Café',
            'slug' => 'bean-brew-cafe',
        ]);

        $this->seed(CafeMenuSeeder::class);

        $this->assertDatabaseHas('categories', [
            'restaurant_id' => $restaurant->id,
            'name' => 'Iced Beverages',
        ]);

        $icedClassic = Product::query()
            ->where('restaurant_id', $restaurant->id)
            ->where('slug', 'iced-coffee-classic')
            ->first();

        $this->assertNotNull($icedClassic);
        $this->assertSame('109.00', (string) $icedClassic->price);
        $this->assertSame(100, $icedClassic->stock);

        $flavours = ProductAddon::query()
            ->where('product_id', $icedClassic->id)
            ->orderBy('sort_order')
            ->pluck('name')
            ->all();

        $this->assertSame(['Hazelnut', 'Caramel', 'Vanilla', 'Tiramisu'], $flavours);

        $this->assertSame(
            '19.00',
            (string) ProductAddon::query()
                ->where('product_id', $icedClassic->id)
                ->where('name', 'Hazelnut')
                ->value('price'),
        );

        $hotCoffee = Product::query()
            ->where('restaurant_id', $restaurant->id)
            ->where('slug', 'hot-filter-coffee')
            ->first();

        $this->assertSame(
            '10.00',
            (string) ProductAddon::query()
                ->where('product_id', $hotCoffee->id)
                ->where('name', 'Vanilla')
                ->value('price'),
        );

        $sandwich = Product::query()
            ->where('restaurant_id', $restaurant->id)
            ->where('slug', 'crispy-veg-ciabatta-sandwich')
            ->first();

        $this->assertNotNull(
            ProductAddon::query()
                ->where('product_id', $sandwich->id)
                ->where('name', 'Extra Cheese')
                ->where('price', 20)
                ->exists()
        );

        $this->assertSame(12, Product::query()->where('restaurant_id', $restaurant->id)->count());
    }
}