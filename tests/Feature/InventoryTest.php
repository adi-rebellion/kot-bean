<?php

namespace Tests\Feature;

use App\Enums\InventoryTransactionType;
use App\Models\Product;
use App\Models\Restaurant;
use App\Models\User;
use App\Services\InventoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InventoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_manual_stock_adjustment_creates_transaction(): void
    {
        $restaurant = Restaurant::create(['name' => 'Test', 'slug' => 'test']);
        $user = User::factory()->create(['restaurant_id' => $restaurant->id]);

        $category = \App\Models\Category::create([
            'restaurant_id' => $restaurant->id,
            'name' => 'Tea',
            'slug' => 'tea',
        ]);

        $product = Product::create([
            'restaurant_id' => $restaurant->id,
            'category_id' => $category->id,
            'name' => 'Masala Tea',
            'slug' => 'masala-tea',
            'price' => 60,
            'stock' => 10,
            'min_stock' => 5,
        ]);

        $this->actingAs($user);

        $service = app(InventoryService::class);
        $transaction = $service->adjustStock(
            $product,
            5,
            InventoryTransactionType::Restock,
            $user,
            null,
            'Weekly restock',
        );

        $product->refresh();

        $this->assertEquals(15, $product->stock);
        $this->assertEquals(InventoryTransactionType::Restock, $transaction->type);
        $this->assertEquals(10, $transaction->previous_stock);
        $this->assertEquals(15, $transaction->new_stock);
    }
}
