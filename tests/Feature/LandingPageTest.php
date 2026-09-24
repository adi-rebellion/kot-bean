<?php

namespace Tests\Feature;

use App\Models\Restaurant;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LandingPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_see_landing_page_at_root(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertSee('KotBean')
            ->assertSee('Start free')
            ->assertSee('Features');
    }

    public function test_authenticated_users_are_redirected_to_dashboard(): void
    {
        $this->seed(PermissionSeeder::class);

        $restaurant = Restaurant::create([
            'name' => 'Test Café',
            'slug' => 'test-cafe',
            'default_tax_rate' => 0,
        ]);

        $role = Role::create([
            'restaurant_id' => $restaurant->id,
            'slug' => 'owner',
            'name' => 'Owner',
            'is_system' => true,
        ]);

        $user = User::factory()->create([
            'restaurant_id' => $restaurant->id,
            'role_id' => $role->id,
        ]);

        $this->actingAs($user)
            ->get(route('home'))
            ->assertRedirect(route('dashboard'));
    }
}
