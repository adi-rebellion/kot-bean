<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Livewire\PosPage;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Models\Restaurant;
use App\Models\Role;
use App\Models\User;
use App\Services\OrderService;
use App\Services\PaymentService;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PosCustomerTest extends TestCase
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

    public function test_pos_attaches_new_customer_to_order(): void
    {
        Livewire::actingAs($this->user)
            ->test(PosPage::class)
            ->call('addProduct', $this->product->id)
            ->set('customerName', 'Ravi Kumar')
            ->set('customerPhone', '9876543210')
            ->call('saveDraft');

        $order = Order::query()->latest()->first();

        $this->assertNotNull($order);
        $this->assertNotNull($order->customer_id);

        $customer = Customer::find($order->customer_id);
        $this->assertSame('Ravi Kumar', $customer->name);
        $this->assertSame('9876543210', $customer->phone);
    }

    public function test_pos_reuses_existing_customer_by_phone(): void
    {
        $existing = Customer::create([
            'restaurant_id' => $this->user->restaurant_id,
            'name' => 'Old Name',
            'phone' => '9876543210',
        ]);

        Livewire::actingAs($this->user)
            ->test(PosPage::class)
            ->call('addProduct', $this->product->id)
            ->set('customerName', 'Updated Name')
            ->set('customerPhone', '+91 98765 43210')
            ->call('saveDraft');

        $order = Order::query()->latest()->first();

        $this->assertSame($existing->id, $order->customer_id);
        $this->assertSame('Updated Name', $existing->fresh()->name);
        $this->assertSame(1, Customer::query()->where('phone', '9876543210')->count());
    }

    public function test_completed_order_updates_customer_stats(): void
    {
        $orderService = app(OrderService::class);
        $paymentService = app(PaymentService::class);

        $order = $orderService->createDraft(['type' => 'takeaway'], $this->user);
        $orderService->addItem($order, $this->product, 1);
        $orderService->confirm($order);
        $orderService->sendToKitchen($order, $this->user);

        $customer = Customer::create([
            'restaurant_id' => $this->user->restaurant_id,
            'name' => 'Regular Customer',
            'phone' => '9123456780',
        ]);

        $order->update(['customer_id' => $customer->id]);

        $paymentService->recordPayment($order, PaymentMethod::Cash, $order->total, $this->user);
        $paymentService->completeOrder($order->fresh());

        $customer->refresh();

        $this->assertSame(OrderStatus::Completed, $order->fresh()->status);
        $this->assertSame(1, $customer->total_orders);
        $this->assertSame((float) $order->total, (float) $customer->total_spent);
        $this->assertNotNull($customer->last_order_at);
    }
}
