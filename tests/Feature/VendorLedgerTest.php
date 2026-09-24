<?php

namespace Tests\Feature;

use App\Enums\ExpensePaymentStatus;
use App\Livewire\ExpensesIndex;
use App\Livewire\VendorLedger;
use App\Livewire\VendorsIndex;
use App\Models\Expense;
use App\Models\Restaurant;
use App\Models\Role;
use App\Models\User;
use App\Models\Vendor;
use App\Services\VendorService;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class VendorLedgerTest extends TestCase
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
        $role->givePermissionTo('expenses.view');
        $role->givePermissionTo('expenses.manage');

        $this->user = User::factory()->create([
            'restaurant_id' => $this->restaurant->id,
            'role_id' => $role->id,
        ]);
    }

    public function test_user_can_create_vendor(): void
    {
        Livewire::actingAs($this->user)
            ->test(VendorsIndex::class)
            ->set('showForm', true)
            ->set('name', 'Bean Supplier')
            ->set('phone', '9876543210')
            ->call('save');

        $this->assertDatabaseHas('vendors', [
            'restaurant_id' => $this->restaurant->id,
            'name' => 'Bean Supplier',
            'phone' => '9876543210',
        ]);
    }

    public function test_expense_can_be_mapped_to_vendor_as_pending(): void
    {
        $vendor = Vendor::create([
            'restaurant_id' => $this->restaurant->id,
            'name' => 'Bean Supplier',
        ]);

        Livewire::actingAs($this->user)
            ->test(ExpensesIndex::class)
            ->set('showForm', true)
            ->set('title', 'Coffee beans')
            ->set('category', 'raw_materials')
            ->set('vendor_id', $vendor->id)
            ->set('amount', '5000')
            ->set('payment_status', ExpensePaymentStatus::Pending->value)
            ->set('expense_date', now()->toDateString())
            ->call('save');

        $expense = Expense::query()->first();

        $this->assertSame($vendor->id, $expense->vendor_id);
        $this->assertSame(ExpensePaymentStatus::Pending, $expense->payment_status);
        $this->assertSame(0.0, (float) $expense->paid_amount);
    }

    public function test_vendor_ledger_filters_pending_entries(): void
    {
        $vendor = Vendor::create([
            'restaurant_id' => $this->restaurant->id,
            'name' => 'Bean Supplier',
        ]);

        Expense::create([
            'restaurant_id' => $this->restaurant->id,
            'vendor_id' => $vendor->id,
            'title' => 'Pending bill',
            'category' => 'supplies',
            'amount' => 1000,
            'payment_status' => ExpensePaymentStatus::Pending,
            'paid_amount' => 0,
            'expense_date' => now(),
            'created_by' => $this->user->id,
        ]);

        Expense::create([
            'restaurant_id' => $this->restaurant->id,
            'vendor_id' => $vendor->id,
            'title' => 'Paid bill',
            'category' => 'supplies',
            'amount' => 500,
            'payment_status' => ExpensePaymentStatus::Paid,
            'paid_amount' => 500,
            'expense_date' => now(),
            'created_by' => $this->user->id,
        ]);

        Livewire::actingAs($this->user)
            ->test(VendorLedger::class)
            ->set('statusFilter', ExpensePaymentStatus::Pending->value)
            ->assertSee('Pending bill')
            ->assertDontSee('Paid bill');
    }

    public function test_vendor_payment_updates_ledger_balance(): void
    {
        $vendor = Vendor::create([
            'restaurant_id' => $this->restaurant->id,
            'name' => 'Bean Supplier',
        ]);

        $expense = Expense::create([
            'restaurant_id' => $this->restaurant->id,
            'vendor_id' => $vendor->id,
            'title' => 'Pending bill',
            'category' => 'supplies',
            'amount' => 1000,
            'payment_status' => ExpensePaymentStatus::Pending,
            'paid_amount' => 0,
            'expense_date' => now(),
            'created_by' => $this->user->id,
        ]);

        app(VendorService::class)->markPaid($expense);

        $expense->refresh();

        $this->assertSame(ExpensePaymentStatus::Paid, $expense->payment_status);
        $this->assertSame(1000.0, (float) $expense->paid_amount);
        $this->assertSame(0.0, $expense->balance_due);
    }
}
