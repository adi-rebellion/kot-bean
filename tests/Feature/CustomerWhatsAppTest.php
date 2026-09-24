<?php

namespace Tests\Feature;

use App\Livewire\CustomersIndex;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Permission;
use App\Models\Product;
use App\Models\Restaurant;
use App\Models\Role;
use App\Models\User;
use App\Services\CustomerWhatsAppService;
use App\Services\TwilioWhatsAppService;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

class CustomerWhatsAppTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private User $cashier;

    private Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PermissionSeeder::class);

        config([
            'services.twilio.sid' => 'ACtest123',
            'services.twilio.auth_token' => 'test-token',
            'services.twilio.whatsapp_from' => 'whatsapp:+14155238886',
            'services.twilio.whatsapp_content_sid' => 'HXtestcontent123',
        ]);

        $restaurant = Restaurant::create([
            'name' => 'Test Café',
            'slug' => 'test-cafe',
            'timezone' => 'Asia/Kolkata',
            'default_tax_rate' => 5,
            'settings' => ['tables_enabled' => false],
        ]);

        $ownerRole = Role::create([
            'restaurant_id' => $restaurant->id,
            'slug' => 'owner',
            'name' => 'Owner',
            'is_system' => true,
        ]);
        $ownerRole->permissions()->sync(
            Permission::pluck('id')
        );

        $cashierRole = Role::create([
            'restaurant_id' => $restaurant->id,
            'slug' => 'cashier',
            'name' => 'Cashier',
            'is_system' => true,
        ]);
        $cashierRole->permissions()->sync(
            Permission::whereIn('slug', ['customers.view'])->pluck('id')
        );

        $this->owner = User::factory()->create([
            'restaurant_id' => $restaurant->id,
            'role_id' => $ownerRole->id,
        ]);

        $this->cashier = User::factory()->create([
            'restaurant_id' => $restaurant->id,
            'role_id' => $cashierRole->id,
        ]);

        $this->customer = Customer::create([
            'restaurant_id' => $restaurant->id,
            'name' => 'Ravi Kumar',
            'phone' => '9876543210',
            'total_orders' => 1,
            'total_spent' => 210,
            'last_order_at' => now(),
        ]);

        $category = Category::create([
            'restaurant_id' => $restaurant->id,
            'name' => 'Coffee',
            'slug' => 'coffee',
        ]);

        $product = Product::create([
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

        $order = Order::create([
            'restaurant_id' => $restaurant->id,
            'customer_id' => $this->customer->id,
            'order_number' => 'ORD-20260925-0001',
            'created_by' => $this->owner->id,
            'type' => 'dine_in',
            'status' => 'completed',
            'subtotal' => 200,
            'tax_amount' => 10,
            'discount_amount' => 0,
            'total' => 210,
            'completed_at' => now(),
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_name' => 'Espresso',
            'quantity' => 2,
            'unit_price' => 100,
            'tax_rate' => 5,
            'tax_amount' => 10,
            'discount_amount' => 0,
            'total' => 210,
        ]);
    }

    public function test_builds_last_order_whatsapp_message(): void
    {
        $this->customer->load(['latestOrder.items', 'restaurant']);

        $message = app(CustomerWhatsAppService::class)->buildLastOrderMessage($this->customer);

        $this->assertStringContainsString('Hi Ravi Kumar,', $message);
        $this->assertStringContainsString('ORD-20260925-0001', $message);
        $this->assertStringContainsString('2x Espresso', $message);
        $this->assertStringContainsString('₹210.00', $message);
    }

    public function test_sends_whatsapp_message_via_twilio(): void
    {
        Http::fake([
            'api.twilio.com/*' => Http::response(['sid' => 'SM123'], 201),
        ]);

        $sid = app(CustomerWhatsAppService::class)->sendLastOrderMessage($this->customer);

        $this->assertSame('SM123', $sid);

        Http::assertSent(function ($request) {
            $variables = json_decode($request['ContentVariables'], true);

            return $request->url() === 'https://api.twilio.com/2010-04-01/Accounts/ACtest123/Messages.json'
                && $request['To'] === 'whatsapp:+919876543210'
                && $request['From'] === 'whatsapp:+14155238886'
                && $request['ContentSid'] === 'HXtestcontent123'
                && ($variables['3'] ?? null) === 'ORD-20260925-0001'
                && ($variables['1'] ?? null) === 'Ravi Kumar';
        });
    }

    public function test_whatsapp_tab_shows_customers_with_orders(): void
    {
        Livewire::actingAs($this->owner)
            ->test(CustomersIndex::class)
            ->set('tab', 'whatsapp')
            ->assertSee('Ravi Kumar')
            ->assertSee('9876543210')
            ->assertSee('ORD-20260925-0001');
    }

    public function test_owner_can_send_whatsapp_from_customers_page(): void
    {
        Http::fake([
            'api.twilio.com/*' => Http::response(['sid' => 'SM456'], 201),
        ]);

        Livewire::actingAs($this->owner)
            ->test(CustomersIndex::class)
            ->set('tab', 'whatsapp')
            ->call('selectCustomer', $this->customer->id)
            ->call('sendMessage', $this->customer->id)
            ->assertSessionHas('success');

        Http::assertSentCount(1);
    }

    public function test_cashier_cannot_send_whatsapp_messages(): void
    {
        Livewire::actingAs($this->cashier)
            ->test(CustomersIndex::class)
            ->set('tab', 'whatsapp')
            ->call('sendMessage', $this->customer->id)
            ->assertForbidden();
    }

    public function test_formats_indian_phone_numbers_for_whatsapp(): void
    {
        $service = app(TwilioWhatsAppService::class);

        $this->assertSame('whatsapp:+919876543210', $service->formatWhatsAppNumber('9876543210'));
        $this->assertSame('whatsapp:+919876543210', $service->formatWhatsAppNumber('+91 98765 43210'));
    }
}
