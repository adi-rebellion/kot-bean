<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Livewire\DailySalesLedgerIndex;
use App\Models\DailySalesLedger;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Restaurant;
use App\Models\Role;
use App\Models\User;
use App\Services\DailySalesLedgerService;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class DailySalesLedgerTest extends TestCase
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
            'default_tax_rate' => 0,
        ]);

        $role = Role::create([
            'restaurant_id' => $this->restaurant->id,
            'slug' => 'owner',
            'name' => 'Owner',
            'is_system' => true,
        ]);
        $role->givePermissionTo('reports.view');
        $role->givePermissionTo('payments.process');

        $this->user = User::factory()->create([
            'restaurant_id' => $this->restaurant->id,
            'role_id' => $role->id,
        ]);
    }

    public function test_user_can_save_daily_sales_entry(): void
    {
        Livewire::actingAs($this->user)
            ->test(DailySalesLedgerIndex::class)
            ->set('saleDate', today()->toDateString())
            ->set('cashAmount', '1500')
            ->set('onlineAmount', '2300.50')
            ->set('notes', 'Missed POS after rush')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('daily_sales_ledgers', [
            'restaurant_id' => $this->restaurant->id,
            'sale_date' => today()->toDateString(),
            'cash_amount' => '1500.00',
            'online_amount' => '2300.50',
            'notes' => 'Missed POS after rush',
            'recorded_by' => $this->user->id,
        ]);
    }

    public function test_saving_same_date_updates_existing_entry(): void
    {
        DailySalesLedger::create([
            'restaurant_id' => $this->restaurant->id,
            'sale_date' => today(),
            'cash_amount' => 100,
            'online_amount' => 200,
            'recorded_by' => $this->user->id,
        ]);

        Livewire::actingAs($this->user)
            ->test(DailySalesLedgerIndex::class)
            ->set('saleDate', today()->toDateString())
            ->set('cashAmount', '500')
            ->set('onlineAmount', '600')
            ->call('save');

        $this->assertSame(1, DailySalesLedger::query()->count());
        $this->assertDatabaseHas('daily_sales_ledgers', [
            'restaurant_id' => $this->restaurant->id,
            'sale_date' => today()->toDateString(),
            'cash_amount' => '500.00',
            'online_amount' => '600.00',
        ]);
    }

    public function test_pos_totals_split_cash_and_online(): void
    {
        $orderCash = Order::create([
            'restaurant_id' => $this->restaurant->id,
            'order_number' => 'ORD-DS-001',
            'created_by' => $this->user->id,
            'type' => 'dine_in',
            'status' => OrderStatus::Completed,
            'total' => 300,
        ]);

        Payment::create([
            'restaurant_id' => $this->restaurant->id,
            'order_id' => $orderCash->id,
            'payment_number' => 'PAY-DS-001',
            'method' => PaymentMethod::Cash,
            'amount' => 300,
            'received_by' => $this->user->id,
            'created_at' => today()->setTime(12, 0),
        ]);

        $orderUpi = Order::create([
            'restaurant_id' => $this->restaurant->id,
            'order_number' => 'ORD-DS-002',
            'created_by' => $this->user->id,
            'type' => 'dine_in',
            'status' => OrderStatus::Completed,
            'total' => 450,
        ]);

        Payment::create([
            'restaurant_id' => $this->restaurant->id,
            'order_id' => $orderUpi->id,
            'payment_number' => 'PAY-DS-002',
            'method' => PaymentMethod::Upi,
            'amount' => 450,
            'received_by' => $this->user->id,
            'created_at' => today()->setTime(14, 0),
        ]);

        $totals = app(DailySalesLedgerService::class)->posTotalsForDate($this->restaurant, today());

        $this->assertSame(300.0, $totals['cash']);
        $this->assertSame(450.0, $totals['online']);
        $this->assertSame(750.0, $totals['total']);
    }

    public function test_viewer_without_payments_permission_cannot_save(): void
    {
        $viewerRole = Role::create([
            'restaurant_id' => $this->restaurant->id,
            'slug' => 'viewer',
            'name' => 'Viewer',
            'is_system' => false,
        ]);
        $viewerRole->givePermissionTo('reports.view');

        $viewer = User::factory()->create([
            'restaurant_id' => $this->restaurant->id,
            'role_id' => $viewerRole->id,
        ]);

        Livewire::actingAs($viewer)
            ->test(DailySalesLedgerIndex::class)
            ->set('cashAmount', '100')
            ->set('onlineAmount', '100')
            ->call('save')
            ->assertForbidden();
    }
}