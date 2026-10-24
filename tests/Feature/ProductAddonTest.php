<?php

namespace Tests\Feature;

use App\Livewire\Menu\ProductForm;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductAddon;
use App\Models\Restaurant;
use App\Models\Role;
use App\Models\User;
use App\Services\OrderService;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ProductAddonTest extends TestCase
{
    use RefreshDatabase;

    private User $manager;

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

        $this->manager = User::factory()->create([
            'restaurant_id' => $restaurant->id,
            'role_id' => $managerRole->id,
        ]);

        $category = Category::create([
            'restaurant_id' => $restaurant->id,
            'name' => 'Snacks',
            'slug' => 'snacks',
        ]);

        $this->product = Product::create([
            'restaurant_id' => $restaurant->id,
            'category_id' => $category->id,
            'name' => 'Sandwich',
            'slug' => 'sandwich',
            'price' => 120,
            'stock' => 50,
            'track_inventory' => false,
            'is_available' => true,
        ]);
    }

    public function test_manager_can_add_product_customization(): void
    {
        Livewire::actingAs($this->manager)
            ->test(ProductForm::class, ['product' => $this->product])
            ->set('addonName', 'Extra cheese')
            ->set('addonPrice', '20')
            ->call('saveAddon')
            ->assertHasNoErrors();

        $addon = ProductAddon::query()->where('product_id', $this->product->id)->first();

        $this->assertNotNull($addon);
        $this->assertSame('Extra cheese', $addon->name);
        $this->assertSame('20.00', (string) $addon->price);
    }

    public function test_order_item_includes_customization_price_in_unit_price(): void
    {
        $addon = ProductAddon::create([
            'product_id' => $this->product->id,
            'name' => 'Extra cheese',
            'price' => 20,
        ]);

        $orderService = app(OrderService::class);
        $order = $orderService->createDraft(['type' => 'dine_in'], $this->manager);

        $item = $orderService->addItem($order, $this->product, 1, null, null, [
            [
                'addon_id' => $addon->id,
                'name' => $addon->name,
                'price' => 20.0,
            ],
        ]);

        $this->assertSame('140.00', (string) $item->unit_price);
        $this->assertNotNull($item->modifiers_fingerprint);
        $this->assertCount(1, $item->modifiers);
    }

    public function test_menu_form_hides_disabled_fields_from_restaurant_settings(): void
    {
        $restaurant = $this->manager->restaurant;
        $restaurant->update([
            'settings' => array_merge($restaurant->settings ?? [], [
                'menu_product_fields' => [
                    'description' => false,
                    'sku' => false,
                    'cost_price' => false,
                    'tax_rate' => false,
                    'preparation_time' => false,
                    'inventory' => false,
                    'variants' => false,
                    'addons' => true,
                    'sort_order' => false,
                    'image' => false,
                ],
            ]),
        ]);

        Livewire::actingAs($this->manager)
            ->test(ProductForm::class)
            ->assertDontSee('Description')
            ->assertDontSee('Track inventory')
            ->assertSee('Customizations');
    }
}
