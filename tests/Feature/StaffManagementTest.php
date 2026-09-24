<?php

namespace Tests\Feature;

use App\Livewire\StaffIndex;
use App\Models\Restaurant;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class StaffManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private Role $cashierRole;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PermissionSeeder::class);

        $restaurant = Restaurant::create([
            'name' => 'Test Café',
            'slug' => 'test-cafe',
            'default_tax_rate' => 5,
        ]);

        $ownerRole = Role::create([
            'restaurant_id' => $restaurant->id,
            'slug' => 'owner',
            'name' => 'Owner',
            'is_system' => true,
        ]);
        $ownerRole->givePermissionTo('staff.manage');

        $this->cashierRole = Role::create([
            'restaurant_id' => $restaurant->id,
            'slug' => 'cashier',
            'name' => 'Cashier',
            'is_system' => true,
        ]);

        $this->owner = User::factory()->create([
            'restaurant_id' => $restaurant->id,
            'role_id' => $ownerRole->id,
            'is_active' => true,
        ]);
    }

    public function test_owner_can_add_staff_with_mobile_number_and_random_password(): void
    {
        Livewire::actingAs($this->owner)
            ->test(StaffIndex::class)
            ->set('showForm', true)
            ->set('name', 'Ravi Cashier')
            ->set('phone', '+91 98765 43210')
            ->set('role_id', $this->cashierRole->id)
            ->call('save')
            ->assertSet('createdStaffName', 'Ravi Cashier')
            ->assertSet('createdStaffPhone', '9876543210')
            ->assertSet('generatedPassword', fn (?string $password): bool => is_string($password) && strlen($password) >= 8);

        $staff = User::query()->where('phone', '9876543210')->first();

        $this->assertNotNull($staff);
        $this->assertSame('Ravi Cashier', $staff->name);
        $this->assertSame($this->cashierRole->id, $staff->role_id);
        $this->assertSame('9876543210@staff.test-cafe.kotbean', $staff->email);
        $this->assertTrue($staff->is_active);
    }

    public function test_staff_can_login_with_mobile_number(): void
    {
        $password = 'TempPass123';

        User::factory()->create([
            'restaurant_id' => $this->owner->restaurant_id,
            'role_id' => $this->cashierRole->id,
            'name' => 'Mobile Staff',
            'email' => '9998887776@staff.test-cafe.kotbean',
            'phone' => '9998887776',
            'password' => Hash::make($password),
            'is_active' => true,
        ]);

        $response = $this->post('/login', [
            'login' => '9998887776',
            'password' => $password,
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));
    }

    public function test_duplicate_mobile_number_is_rejected(): void
    {
        User::factory()->create([
            'restaurant_id' => $this->owner->restaurant_id,
            'role_id' => $this->cashierRole->id,
            'phone' => '9876543210',
            'is_active' => true,
        ]);

        Livewire::actingAs($this->owner)
            ->test(StaffIndex::class)
            ->set('showForm', true)
            ->set('name', 'Another Staff')
            ->set('phone', '9876543210')
            ->set('role_id', $this->cashierRole->id)
            ->call('save')
            ->assertHasErrors(['phone']);
    }
}
