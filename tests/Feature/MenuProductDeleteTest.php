<?php

namespace Tests\Feature;

use App\Livewire\Menu\ProductsIndex;
use App\Models\Category;
use App\Models\Product;
use App\Models\Restaurant;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class MenuProductDeleteTest extends TestCase
{
    use RefreshDatabase;

    private User $manager;

    private User $cashier;

    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PermissionSeeder::class);

        $restaurant = Restaurant::create([
            'name' => 'Test Café',
            'slug' => 'test-cafe',
            'default_tax_rate' => 5,
        ]);

        $managerRole = Role::create([
            'restaurant_id' => $restaurant->id,
            'slug' => 'manager',
            'name' => 'Manager',
            'is_system' => true,
        ]);
        $managerRole->givePermissionTo('menu.manage');
        $managerRole->givePermissionTo('menu.view');

        $cashierRole = Role::create([
            'restaurant_id' => $restaurant->id,
            'slug' => 'cashier',
            'name' => 'Cashier',
            'is_system' => true,
        ]);
        $cashierRole->givePermissionTo('menu.view');

        $this->manager = User::factory()->create([
            'restaurant_id' => $restaurant->id,
            'role_id' => $managerRole->id,
        ]);

        $this->cashier = User::factory()->create([
            'restaurant_id' => $restaurant->id,
            'role_id' => $cashierRole->id,
        ]);

        $category = Category::create([
            'restaurant_id' => $restaurant->id,
            'name' => 'Coffee',
            'slug' => 'coffee',
        ]);

        $this->product = Product::create([
            'restaurant_id' => $restaurant->id,
            'category_id' => $category->id,
            'name' => 'Espresso',
            'slug' => 'espresso',
            'price' => 100,
            'cost_price' => 30,
            'stock' => 10,
            'min_stock' => 5,
            'is_available' => true,
        ]);
    }

    public function test_manager_can_delete_product_from_menu(): void
    {
        Livewire::actingAs($this->manager)
            ->test(ProductsIndex::class)
            ->call('delete', $this->product->id);

        $this->assertSoftDeleted('products', ['id' => $this->product->id]);
    }

    public function test_cashier_cannot_delete_product_from_menu(): void
    {
        Livewire::actingAs($this->cashier)
            ->test(ProductsIndex::class)
            ->call('delete', $this->product->id)
            ->assertForbidden();

        $this->assertDatabaseHas('products', [
            'id' => $this->product->id,
            'deleted_at' => null,
        ]);
    }

    public function test_deleted_product_is_hidden_from_menu_list(): void
    {
        Livewire::actingAs($this->manager)
            ->test(ProductsIndex::class)
            ->call('delete', $this->product->id)
            ->assertDontSee('Espresso');
    }
}
