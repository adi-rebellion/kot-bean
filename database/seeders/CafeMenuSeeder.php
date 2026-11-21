<?php

namespace Database\Seeders;

use App\Enums\InventoryTransactionType;
use App\Models\Category;
use App\Models\InventoryTransaction;
use App\Models\Product;
use App\Models\ProductAddon;
use App\Models\Restaurant;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CafeMenuSeeder extends Seeder
{
    /** @var array<int, array{name: string, price: float, stock: int, addons?: array<int, array{name: string, price: float}>}> */
    private const MENU = [
        'Iced Beverages' => [
            [
                'name' => 'Iced Coffee - Classic',
                'slug' => 'iced-coffee-classic',
                'price' => 109,
                'stock' => 100,
                'addons' => [
                    ['name' => 'Hazelnut', 'price' => 19],
                    ['name' => 'Caramel', 'price' => 19],
                    ['name' => 'Vanilla', 'price' => 19],
                    ['name' => 'Tiramisu', 'price' => 19],
                ],
            ],
            ['name' => 'Vietnamese Iced Coffee', 'slug' => 'vietnamese-iced-coffee', 'price' => 129, 'stock' => 100],
            ['name' => 'Nutella Iced Coffee', 'slug' => 'nutella-iced-coffee', 'price' => 149, 'stock' => 100],
            ['name' => 'Lotus Biscoff Iced Coffee', 'slug' => 'lotus-biscoff-iced-coffee', 'price' => 139, 'stock' => 100],
            ['name' => 'Coconut Cloud Iced Coffee', 'slug' => 'coconut-cloud-iced-coffee', 'price' => 159, 'stock' => 100],
        ],
        'Hot Beverages' => [
            [
                'name' => 'Hot Filter Coffee',
                'slug' => 'hot-filter-coffee',
                'price' => 59,
                'stock' => 100,
                'addons' => [
                    ['name' => 'Hazelnut', 'price' => 10],
                    ['name' => 'Caramel', 'price' => 10],
                    ['name' => 'Vanilla', 'price' => 10],
                    ['name' => 'Tiramisu', 'price' => 10],
                ],
            ],
            ['name' => 'Hot Dark Chocolate', 'slug' => 'hot-dark-chocolate', 'price' => 119, 'stock' => 100],
            ['name' => 'Hot Dark Chocolate with Brownie', 'slug' => 'hot-dark-chocolate-with-brownie', 'price' => 159, 'stock' => 100],
        ],
        'Savoury' => [
            [
                'name' => 'Crispy Veg Ciabatta Sandwich',
                'slug' => 'crispy-veg-ciabatta-sandwich',
                'price' => 119,
                'stock' => 100,
                'addons' => [
                    ['name' => 'Extra Cheese', 'price' => 20],
                ],
            ],
            [
                'name' => 'Paneer Tikka Ciabatta Sandwich',
                'slug' => 'paneer-tikka-ciabatta-sandwich',
                'price' => 129,
                'stock' => 100,
                'addons' => [
                    ['name' => 'Extra Cheese', 'price' => 20],
                ],
            ],
        ],
        'Bakery' => [
            ['name' => 'Brownie', 'slug' => 'brownie', 'price' => 79, 'stock' => 100],
        ],
    ];

    public function run(?Restaurant $restaurant = null): void
    {
        $restaurant ??= $this->resolveRestaurant();

        if (! $restaurant) {
            $this->command?->error('No restaurant found. Create a restaurant first or run DemoRestaurantSeeder.');

            return;
        }

        $categorySort = 0;
        $productSort = 0;

        foreach (self::MENU as $categoryName => $items) {
            $categorySlug = Str::slug($categoryName);

            $category = Category::updateOrCreate(
                [
                    'restaurant_id' => $restaurant->id,
                    'slug' => $categorySlug,
                ],
                [
                    'name' => $categoryName,
                    'sort_order' => $categorySort++,
                    'is_active' => true,
                ],
            );

            foreach ($items as $item) {
                $slug = $item['slug'] ?? Str::slug($item['name']);

                [$product, $created] = $this->upsertProduct($restaurant->id, $category->id, $slug, [
                    'name' => $item['name'],
                    'description' => null,
                    'price' => $item['price'],
                    'cost_price' => 0,
                    'stock' => $item['stock'],
                    'min_stock' => 5,
                    'preparation_time' => 10,
                    'has_variants' => false,
                    'track_inventory' => true,
                    'is_available' => true,
                    'sort_order' => $productSort++,
                ]);

                if ($created) {
                    InventoryTransaction::create([
                        'restaurant_id' => $restaurant->id,
                        'product_id' => $product->id,
                        'type' => InventoryTransactionType::OpeningStock,
                        'quantity' => $item['stock'],
                        'previous_stock' => 0,
                        'new_stock' => $item['stock'],
                        'reason' => 'Menu seed opening stock',
                    ]);
                } else {
                    $previous = (int) $product->stock;
                    if ($previous !== $item['stock']) {
                        $product->update(['stock' => $item['stock']]);
                    }
                }

                $addonSort = 0;
                foreach ($item['addons'] ?? [] as $addon) {
                    ProductAddon::updateOrCreate(
                        [
                            'product_id' => $product->id,
                            'name' => $addon['name'],
                        ],
                        [
                            'price' => $addon['price'],
                            'is_available' => true,
                            'sort_order' => $addonSort++,
                        ],
                    );
                }
            }
        }

        $this->command?->info("Café menu seeded for {$restaurant->name} ({$restaurant->slug}).");
    }

    private function resolveRestaurant(): ?Restaurant
    {
        $slug = config('seeding.restaurant_slug');

        if ($slug) {
            return Restaurant::query()->where('slug', $slug)->first();
        }

        return Restaurant::query()->where('slug', 'bean-brew-cafe')->first()
            ?? Restaurant::query()->orderBy('id')->first();
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array{0: Product, 1: bool}
     */
    private function upsertProduct(int $restaurantId, int $categoryId, string $slug, array $attributes): array
    {
        $product = Product::withoutGlobalScopes()
            ->withTrashed()
            ->where('restaurant_id', $restaurantId)
            ->where('slug', $slug)
            ->first();

        $payload = [
            ...$attributes,
            'restaurant_id' => $restaurantId,
            'category_id' => $categoryId,
            'slug' => $slug,
        ];

        if ($product) {
            if ($product->trashed()) {
                $product->restore();
            }

            $product->update($payload);

            return [$product->fresh(), false];
        }

        $product = Product::withoutGlobalScopes()->create($payload);

        return [$product, true];
    }
}