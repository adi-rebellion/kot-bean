<?php

namespace Tests\Feature;

use App\Enums\AddonSelectionType;
use App\Livewire\Menu\ProductForm;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductAddonGroup;
use App\Models\ProductAddonOption;
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

    public function test_manager_can_create_addon_group_and_option(): void
    {
        Livewire::actingAs($this->manager)
            ->test(ProductForm::class, ['product' => $this->product])
            ->set('addonGroupName', 'Extras')
            ->set('addonGroupSelectionType', 'single')
            ->call('saveAddonGroup')
            ->assertHasNoErrors();

        $group = ProductAddonGroup::query()->where('product_id', $this->product->id)->first();

        $this->assertNotNull($group);
        $this->assertSame('Extras', $group->name);
        $this->assertSame(AddonSelectionType::Single, $group->selection_type);

        Livewire::actingAs($this->manager)
            ->test(ProductForm::class, ['product' => $this->product])
            ->set('addonOptionGroupId', $group->id)
            ->set('addonOptionName', 'Extra cheese')
            ->set('addonOptionPrice', '20')
            ->call('saveAddonOption')
            ->assertHasNoErrors();

        $option = ProductAddonOption::query()->where('product_addon_group_id', $group->id)->first();

        $this->assertNotNull($option);
        $this->assertSame('Extra cheese', $option->name);
        $this->assertSame('20.00', (string) $option->price);
    }

    public function test_order_item_includes_addon_price_in_unit_price(): void
    {
        $group = ProductAddonGroup::create([
            'product_id' => $this->product->id,
            'name' => 'Extras',
            'selection_type' => AddonSelectionType::Single,
        ]);

        $option = ProductAddonOption::create([
            'product_addon_group_id' => $group->id,
            'name' => 'Extra cheese',
            'price' => 20,
        ]);

        $orderService = app(OrderService::class);
        $order = $orderService->createDraft(['type' => 'dine_in'], $this->manager);

        $item = $orderService->addItem($order, $this->product, 1, null, null, [
            [
                'group_id' => $group->id,
                'group_name' => $group->name,
                'option_id' => $option->id,
                'option_name' => $option->name,
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
            ->assertSee('Add-ons');
    }
}
