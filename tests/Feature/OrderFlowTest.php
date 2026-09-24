<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Exceptions\InsufficientStockException;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\Restaurant;
use App\Models\Role;
use App\Models\User;
use App\Services\OrderService;
use App\Services\PaymentService;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderFlowTest extends TestCase
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
        ]);

        $role = Role::create([
            'restaurant_id' => $restaurant->id,
            'slug' => 'owner',
            'name' => 'Owner',
            'is_system' => true,
        ]);

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

    public function test_user_can_create_order_and_add_items(): void
    {
        $this->actingAs($this->user);

        $orderService = app(OrderService::class);
        $order = $orderService->createDraft(['type' => 'dine_in'], $this->user);
        $orderService->addItem($order, $this->product, 2);

        $order->refresh();

        $this->assertEquals(OrderStatus::Draft, $order->status);
        $this->assertCount(1, $order->items);
        $this->assertEquals(200, (float) $order->subtotal);
    }

    public function test_insufficient_stock_prevents_order_item(): void
    {
        $this->actingAs($this->user);

        $this->product->update(['stock' => 1]);

        $orderService = app(OrderService::class);
        $order = $orderService->createDraft(['type' => 'takeaway'], $this->user);

        $this->expectException(InsufficientStockException::class);
        $orderService->addItem($order, $this->product, 5);
    }

    public function test_payment_completes_order_and_deducts_inventory(): void
    {
        $this->actingAs($this->user);

        $orderService = app(OrderService::class);
        $paymentService = app(PaymentService::class);

        $order = $orderService->createDraft(['type' => 'takeaway'], $this->user);
        $orderService->addItem($order, $this->product, 2);
        $orderService->confirm($order);
        $orderService->sendToKitchen($order, $this->user);

        $paymentService->recordPayment($order, PaymentMethod::Upi, $order->total, $this->user);
        $paymentService->completeOrder($order);

        $this->product->refresh();
        $order->refresh();

        $this->assertEquals(OrderStatus::Completed, $order->status);
        $this->assertEquals(8, $this->product->stock);
    }

    public function test_dine_in_order_works_without_table_when_tables_disabled(): void
    {
        $this->actingAs($this->user);

        $this->user->restaurant->update([
            'settings' => ['tables_enabled' => false],
        ]);

        $orderService = app(OrderService::class);
        $paymentService = app(PaymentService::class);

        $order = $orderService->createDraft(['type' => 'dine_in'], $this->user);
        $orderService->addItem($order, $this->product, 1);
        $orderService->confirm($order);
        $orderService->sendToKitchen($order, $this->user);

        $paymentService->recordPayment($order, PaymentMethod::Cash, $order->total, $this->user);
        $paymentService->completeOrder($order);

        $order->refresh();

        $this->assertNull($order->restaurant_table_id);
        $this->assertEquals(OrderStatus::Completed, $order->status);
    }

    public function test_tenant_isolation_on_orders(): void
    {
        $otherRestaurant = Restaurant::create(['name' => 'Other', 'slug' => 'other']);
        $otherOrder = Order::withoutGlobalScopes()->create([
            'restaurant_id' => $otherRestaurant->id,
            'order_number' => 'ORD-OTHER-001',
            'type' => 'dine_in',
            'status' => 'draft',
        ]);

        $this->actingAs($this->user);

        $this->assertNull(Order::find($otherOrder->id));
    }
}
