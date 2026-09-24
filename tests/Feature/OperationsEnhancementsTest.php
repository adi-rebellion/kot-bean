<?php

namespace Tests\Feature;

use App\Enums\InventoryTransactionType;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Livewire\InventoryIndex;
use App\Livewire\Menu\CategoriesIndex;
use App\Livewire\OrdersIndex;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Permission;
use App\Models\Product;
use App\Models\Restaurant;
use App\Models\Role;
use App\Models\User;
use App\Services\CashRegisterService;
use App\Services\LoyaltyService;
use App\Services\OrderService;
use App\Services\PaymentService;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class OperationsEnhancementsTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private Restaurant $restaurant;

    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PermissionSeeder::class);

        $this->restaurant = Restaurant::create([
            'name' => 'Test Café',
            'slug' => 'test-cafe',
            'default_tax_rate' => 0,
            'settings' => [
                'loyalty_enabled' => true,
                'loyalty_points_per_100' => 1,
            ],
        ]);

        $role = Role::create([
            'restaurant_id' => $this->restaurant->id,
            'slug' => 'owner',
            'name' => 'Owner',
            'is_system' => true,
        ]);
        $role->permissions()->sync(Permission::pluck('id'));

        $this->owner = User::factory()->create([
            'restaurant_id' => $this->restaurant->id,
            'role_id' => $role->id,
        ]);

        $category = Category::create([
            'restaurant_id' => $this->restaurant->id,
            'name' => 'Coffee',
            'slug' => 'coffee',
        ]);

        $this->product = Product::create([
            'restaurant_id' => $this->restaurant->id,
            'category_id' => $category->id,
            'name' => 'Espresso',
            'slug' => 'espresso',
            'price' => 200,
            'cost_price' => 50,
            'stock' => 10,
            'min_stock' => 5,
            'track_inventory' => true,
            'is_available' => true,
        ]);
    }

    public function test_order_can_be_cancelled_from_orders_page(): void
    {
        $order = app(OrderService::class)->createDraft(['type' => 'dine_in'], $this->owner);
        app(OrderService::class)->addItem($order, $this->product, 1);

        Livewire::actingAs($this->owner)
            ->test(OrdersIndex::class)
            ->call('cancelOrder', $order->id)
            ->assertHasNoErrors();

        $this->assertSame(OrderStatus::Cancelled, $order->fresh()->status);
    }

    public function test_invoice_and_kot_routes_are_accessible(): void
    {
        $order = Order::create([
            'restaurant_id' => $this->restaurant->id,
            'order_number' => 'ORD-000001',
            'created_by' => $this->owner->id,
            'type' => 'dine_in',
            'status' => OrderStatus::Completed,
            'total' => 200,
        ]);

        $this->actingAs($this->owner)
            ->get(route('orders.invoice', $order))
            ->assertOk();
    }

    public function test_category_can_be_created(): void
    {
        Livewire::actingAs($this->owner)
            ->test(CategoriesIndex::class)
            ->call('create')
            ->set('name', 'Desserts')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('categories', [
            'name' => 'Desserts',
            'restaurant_id' => $this->restaurant->id,
        ]);
    }

    public function test_inventory_stock_can_be_adjusted_from_ui(): void
    {
        Livewire::actingAs($this->owner)
            ->test(InventoryIndex::class)
            ->call('openAdjustForm')
            ->set('productId', $this->product->id)
            ->set('adjustmentType', 'restock')
            ->set('quantity', 5)
            ->call('saveAdjustment')
            ->assertHasNoErrors();

        $this->assertSame(15, $this->product->fresh()->stock);
        $this->assertDatabaseHas('inventory_transactions', [
            'product_id' => $this->product->id,
            'type' => InventoryTransactionType::Restock->value,
        ]);
    }

    public function test_loyalty_points_awarded_on_order_completion(): void
    {
        $customer = Customer::create([
            'restaurant_id' => $this->restaurant->id,
            'name' => 'Ravi',
            'phone' => '9876543210',
        ]);

        $order = Order::create([
            'restaurant_id' => $this->restaurant->id,
            'customer_id' => $customer->id,
            'order_number' => 'ORD-000002',
            'created_by' => $this->owner->id,
            'type' => 'dine_in',
            'status' => OrderStatus::Paid,
            'total' => 250,
        ]);

        app(PaymentService::class)->completeOrder($order);

        $this->assertSame(2, $customer->fresh()->loyalty_points);
        $this->assertDatabaseHas('loyalty_transactions', [
            'customer_id' => $customer->id,
            'points' => 2,
        ]);
    }

    public function test_cash_register_can_be_opened_and_closed(): void
    {
        $service = app(CashRegisterService::class);

        $session = $service->openSession($this->restaurant, $this->owner, 500);

        Payment::create([
            'restaurant_id' => $this->restaurant->id,
            'order_id' => Order::create([
                'restaurant_id' => $this->restaurant->id,
                'order_number' => 'ORD-000003',
                'created_by' => $this->owner->id,
                'type' => 'dine_in',
                'status' => OrderStatus::Completed,
                'total' => 100,
            ])->id,
            'payment_number' => 'PAY-000001',
            'method' => PaymentMethod::Cash,
            'amount' => 100,
            'received_by' => $this->owner->id,
        ]);

        $closed = $service->closeSession($session, $this->owner, 600);

        $this->assertSame(600.0, (float) $closed->actual_cash);
        $this->assertSame(600.0, (float) $closed->expected_cash);
        $this->assertSame(0.0, (float) $closed->cash_difference);
    }

    public function test_loyalty_service_calculates_points(): void
    {
        $service = app(LoyaltyService::class);

        $this->assertSame(2, $service->pointsForOrderTotal($this->restaurant, 250));
        $this->assertTrue($service->isEnabled($this->restaurant));
    }
}
