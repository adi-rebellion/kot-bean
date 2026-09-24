<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Enums\OrderType;
use App\Enums\PaymentMethod;
use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Restaurant;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportExportTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Restaurant $restaurant;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PermissionSeeder::class);

        $this->restaurant = Restaurant::create([
            'name' => 'Test Café',
            'slug' => 'test-cafe',
            'default_tax_rate' => 5,
        ]);

        $role = Role::create([
            'restaurant_id' => $this->restaurant->id,
            'slug' => 'owner',
            'name' => 'Owner',
            'is_system' => true,
        ]);

        $role->givePermissionTo('reports.view');

        $this->user = User::factory()->create([
            'restaurant_id' => $this->restaurant->id,
            'role_id' => $role->id,
        ]);
    }

    public function test_user_can_download_sales_report_csv(): void
    {
        $category = Category::create([
            'restaurant_id' => $this->restaurant->id,
            'name' => 'Coffee',
            'slug' => 'coffee',
        ]);

        $product = Product::create([
            'restaurant_id' => $this->restaurant->id,
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
            'restaurant_id' => $this->restaurant->id,
            'order_number' => 'ORD-001',
            'type' => OrderType::Takeaway,
            'status' => OrderStatus::Completed,
            'subtotal' => 200,
            'tax_amount' => 10,
            'discount_amount' => 0,
            'total' => 210,
            'created_at' => now(),
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_name' => $product->name,
            'quantity' => 2,
            'unit_price' => 100,
            'total' => 200,
        ]);

        Payment::create([
            'restaurant_id' => $this->restaurant->id,
            'order_id' => $order->id,
            'payment_number' => 'PAY-001',
            'method' => PaymentMethod::Cash,
            'amount' => 210,
            'received_by' => $this->user->id,
        ]);

        $dateFrom = now()->toDateString();
        $dateTo = now()->toDateString();

        $response = $this->actingAs($this->user)->get(route('reports.download', [
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
        ]));

        $response->assertOk();
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');
        $response->assertDownload('sales-report-'.$dateFrom.'-to-'.$dateTo.'.csv');

        $csv = $response->streamedContent();

        $this->assertStringContainsString('Sales Report', $csv);
        $this->assertStringContainsString('Test Café', $csv);
        $this->assertStringContainsString('Espresso', $csv);
        $this->assertStringContainsString('Cash', $csv);
        $this->assertStringContainsString('210', $csv);
    }

    public function test_user_without_permission_cannot_download_report(): void
    {
        $role = Role::create([
            'restaurant_id' => $this->restaurant->id,
            'slug' => 'kitchen',
            'name' => 'Kitchen',
            'is_system' => true,
        ]);

        $kitchenUser = User::factory()->create([
            'restaurant_id' => $this->restaurant->id,
            'role_id' => $role->id,
        ]);

        $response = $this->actingAs($kitchenUser)->get(route('reports.download', [
            'date_from' => now()->toDateString(),
            'date_to' => now()->toDateString(),
        ]));

        $response->assertForbidden();
    }

    public function test_guest_cannot_download_report(): void
    {
        $response = $this->get(route('reports.download', [
            'date_from' => now()->toDateString(),
            'date_to' => now()->toDateString(),
        ]));

        $response->assertRedirect(route('login'));
    }

    public function test_download_validates_date_range(): void
    {
        $response = $this->actingAs($this->user)->get(route('reports.download', [
            'date_from' => '2025-09-25',
            'date_to' => '2025-09-01',
        ]));

        $response->assertSessionHasErrors('date_to');
    }
}
