<?php

namespace Tests\Feature;

use App\Livewire\StaffIndex;
use App\Models\Restaurant;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class MultiTenantWorkspaceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PermissionSeeder::class);
    }

    public function test_registration_provisions_a_business_and_owner_role(): void
    {
        $this->post('/register', [
            'name' => 'Asha',
            'business_name' => 'Asha Bakes',
            'email' => 'asha@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertRedirect(route('dashboard', absolute: false));

        $user = User::query()->where('email', 'asha@example.com')->first();
        $restaurant = Restaurant::query()->where('name', 'Asha Bakes')->first();

        $this->assertNotNull($user);
        $this->assertNotNull($restaurant);
        $this->assertSame($restaurant->id, $user->restaurant_id);
        $this->assertTrue($user->isOwner());
        $this->assertTrue($user->hasPermission('dashboard.view'));
        $this->assertTrue($user->belongsToRestaurant($restaurant->id));
    }

    public function test_owner_can_create_and_switch_to_another_business(): void
    {
        $user = $this->registerOwner('First Café', 'one@example.com');
        $firstId = $user->restaurant_id;

        $this->actingAs($user)
            ->post(route('businesses.store'), ['name' => 'Second Café'])
            ->assertRedirect(route('dashboard'));

        $user->refresh();
        $second = Restaurant::query()->where('name', 'Second Café')->first();

        $this->assertNotNull($second);
        $this->assertSame($second->id, $user->restaurant_id);
        $this->assertTrue($user->belongsToRestaurant($firstId));
        $this->assertTrue($user->belongsToRestaurant($second->id));
        $this->assertTrue($user->isOwner());

        $this->actingAs($user)
            ->post(route('businesses.switch'), ['restaurant_id' => $firstId])
            ->assertRedirect(route('dashboard'));

        $this->assertSame($firstId, $user->fresh()->restaurant_id);
    }

    public function test_user_cannot_switch_to_a_business_they_do_not_belong_to(): void
    {
        $owner = $this->registerOwner('Mine', 'mine@example.com');
        $other = Restaurant::create([
            'name' => 'Other Café',
            'slug' => 'other-cafe',
            'default_tax_rate' => 5,
        ]);

        $this->actingAs($owner)
            ->post(route('businesses.switch'), ['restaurant_id' => $other->id])
            ->assertNotFound();

        $this->assertSame($owner->restaurant_id, $owner->fresh()->restaurant_id);
    }

    public function test_existing_user_can_be_invited_and_then_switch_businesses(): void
    {
        $ownerA = $this->registerOwner('Café A', 'a@example.com');
        $ownerB = $this->registerOwner('Café B', 'b@example.com');

        $cashierRole = Role::query()
            ->where('restaurant_id', $ownerA->restaurant_id)
            ->where('slug', 'cashier')
            ->first();

        Livewire::actingAs($ownerA)
            ->test(StaffIndex::class)
            ->set('name', $ownerB->name)
            ->set('email', $ownerB->email)
            ->set('role_id', $cashierRole->id)
            ->call('save')
            ->assertSet('invitedExisting', true);

        $this->assertTrue($ownerB->fresh()->belongsToRestaurant($ownerA->restaurant_id));

        $this->actingAs($ownerB->fresh())
            ->post(route('businesses.switch'), ['restaurant_id' => $ownerA->restaurant_id])
            ->assertRedirect(route('dashboard'));

        $switched = $ownerB->fresh();
        $this->assertSame($ownerA->restaurant_id, $switched->restaurant_id);
        $this->assertTrue($switched->hasPermission('pos.access'));
        $this->assertFalse($switched->isOwner());
    }

    private function registerOwner(string $businessName, string $email): User
    {
        if (auth()->check()) {
            $this->post('/logout');
        }

        $this->post('/register', [
            'name' => 'Owner '.strtok($email, '@'),
            'business_name' => $businessName,
            'email' => $email,
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $user = User::query()->where('email', $email)->firstOrFail();
        $this->post('/logout');

        return $user->fresh();
    }
}
