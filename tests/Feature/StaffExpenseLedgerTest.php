<?php

namespace Tests\Feature;

use App\Enums\ExpensePaymentStatus;
use App\Livewire\ExpensesIndex;
use App\Livewire\StaffExpenseLedger;
use App\Models\Expense;
use App\Models\Restaurant;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class StaffExpenseLedgerTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private User $staffMember;

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

        $ownerRole = Role::create([
            'restaurant_id' => $this->restaurant->id,
            'slug' => 'owner',
            'name' => 'Owner',
            'is_system' => true,
        ]);
        $ownerRole->givePermissionTo('expenses.view');
        $ownerRole->givePermissionTo('expenses.manage');

        $staffRole = Role::create([
            'restaurant_id' => $this->restaurant->id,
            'slug' => 'cashier',
            'name' => 'Cashier',
            'is_system' => true,
        ]);

        $this->owner = User::factory()->create([
            'restaurant_id' => $this->restaurant->id,
            'role_id' => $ownerRole->id,
            'name' => 'Owner User',
        ]);

        $this->staffMember = User::factory()->create([
            'restaurant_id' => $this->restaurant->id,
            'role_id' => $staffRole->id,
            'name' => 'Ravi Staff',
        ]);

        $this->restaurant->members()->syncWithoutDetaching([
            $this->owner->id => ['role_id' => $ownerRole->id],
            $this->staffMember->id => ['role_id' => $staffRole->id],
        ]);
    }

    public function test_expense_stores_paid_by_and_pending_on_staff(): void
    {
        Livewire::actingAs($this->owner)
            ->test(ExpensesIndex::class)
            ->set('showForm', true)
            ->set('title', 'Petty cash groceries')
            ->set('category', 'supplies')
            ->set('amount', '850')
            ->set('expense_date', now()->toDateString())
            ->set('paid_by_user_id', $this->staffMember->id)
            ->set('pending_on_user_id', $this->owner->id)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('expenses', [
            'restaurant_id' => $this->restaurant->id,
            'title' => 'Petty cash groceries',
            'paid_by_user_id' => $this->staffMember->id,
            'pending_on_user_id' => $this->owner->id,
        ]);
    }

    public function test_staff_ledger_lists_attributed_expenses(): void
    {
        Expense::create([
            'restaurant_id' => $this->restaurant->id,
            'title' => 'Advance payment',
            'category' => 'misc',
            'amount' => 1200,
            'payment_status' => ExpensePaymentStatus::Paid,
            'paid_amount' => 1200,
            'expense_date' => now(),
            'created_by' => $this->owner->id,
            'paid_by_user_id' => $this->staffMember->id,
            'pending_on_user_id' => $this->owner->id,
        ]);

        Expense::create([
            'restaurant_id' => $this->restaurant->id,
            'title' => 'Unlabeled expense',
            'category' => 'misc',
            'amount' => 50,
            'payment_status' => ExpensePaymentStatus::Paid,
            'paid_amount' => 50,
            'expense_date' => now(),
            'created_by' => $this->owner->id,
        ]);

        Livewire::actingAs($this->owner)
            ->test(StaffExpenseLedger::class)
            ->assertSee('Advance payment')
            ->assertDontSee('Unlabeled expense');
    }

    public function test_staff_ledger_filters_by_paid_by(): void
    {
        Expense::create([
            'restaurant_id' => $this->restaurant->id,
            'title' => 'Ravi paid',
            'category' => 'misc',
            'amount' => 100,
            'payment_status' => ExpensePaymentStatus::Paid,
            'paid_amount' => 100,
            'expense_date' => now(),
            'created_by' => $this->owner->id,
            'paid_by_user_id' => $this->staffMember->id,
        ]);

        Expense::create([
            'restaurant_id' => $this->restaurant->id,
            'title' => 'Owner pending only',
            'category' => 'misc',
            'amount' => 200,
            'payment_status' => ExpensePaymentStatus::Paid,
            'paid_amount' => 200,
            'expense_date' => now(),
            'created_by' => $this->owner->id,
            'pending_on_user_id' => $this->owner->id,
        ]);

        Livewire::actingAs($this->owner)
            ->test(StaffExpenseLedger::class)
            ->set('involvementFilter', 'paid_by')
            ->assertSee('Ravi paid')
            ->assertDontSee('Owner pending only');
    }
}