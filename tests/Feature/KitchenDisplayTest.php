<?php

namespace Tests\Feature;

use App\Enums\KotStatus;
use App\Livewire\KitchenDisplay;
use App\Models\Category;
use App\Models\Kot;
use App\Models\Product;
use App\Models\Restaurant;
use App\Models\Role;
use App\Models\User;
use App\Services\OrderService;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class KitchenDisplayTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PermissionSeeder::class);

        $restaurant = Restaurant::create([
            'name' => 'Test Café',
            'slug' => 'test-cafe',
            'default_tax_rate' => 5,
            'settings' => ['tables_enabled' => false],
        ]);

        $role = Role::create([
            'restaurant_id' => $restaurant->id,
            'slug' => 'kitchen',
            'name' => 'Kitchen',
            'is_system' => true,
        ]);
        $role->givePermissionTo('kitchen.access');

        $this->user = User::factory()->create([
            'restaurant_id' => $restaurant->id,
            'role_id' => $role->id,
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

    public function test_kitchen_display_dispatches_alert_when_new_pending_kot_arrives(): void
    {
        $this->createPendingKot();

        $component = Livewire::actingAs($this->user)->test(KitchenDisplay::class);

        $this->createPendingKot();

        $component
            ->call('refreshKots')
            ->assertDispatched('kitchen-new-order');
    }

    public function test_kitchen_display_does_not_alert_on_initial_load(): void
    {
        $this->createPendingKot();

        Livewire::actingAs($this->user)
            ->test(KitchenDisplay::class)
            ->assertNotDispatched('kitchen-new-order');
    }

    public function test_kitchen_display_does_not_alert_when_pending_kot_is_removed(): void
    {
        $kot = $this->createPendingKot();

        $component = Livewire::actingAs($this->user)->test(KitchenDisplay::class);

        $kot->update(['status' => KotStatus::Preparing]);

        $component
            ->call('refreshKots')
            ->assertNotDispatched('kitchen-new-order');
    }

    private function createPendingKot(): Kot
    {
        $orderService = app(OrderService::class);

        $order = $orderService->createDraft(['type' => 'takeaway'], $this->user);
        $orderService->addItem($order, $this->product, 1);
        $orderService->confirm($order);

        return $orderService->sendToKitchen($order, $this->user);
    }
}
