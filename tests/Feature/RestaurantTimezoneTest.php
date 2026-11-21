<?php

namespace Tests\Feature;

use App\Models\Restaurant;
use App\Models\Role;
use App\Models\User;
use App\Support\Timezones;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RestaurantTimezoneTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_app_uses_restaurant_timezone(): void
    {
        $this->seed(PermissionSeeder::class);

        $restaurant = Restaurant::create([
            'name' => 'Timezone Café',
            'slug' => 'timezone-cafe',
            'timezone' => 'Asia/Dubai',
        ]);

        $role = Role::create([
            'restaurant_id' => $restaurant->id,
            'slug' => 'owner',
            'name' => 'Owner',
            'is_system' => true,
        ]);
        $role->givePermissionTo('dashboard.view');

        $user = User::factory()->create([
            'restaurant_id' => $restaurant->id,
            'role_id' => $role->id,
        ]);

        $this->actingAs($user)->get(route('dashboard'))->assertOk();

        $this->assertSame('Asia/Dubai', config('app.timezone'));
    }

    public function test_settings_accepts_curated_timezone(): void
    {
        $this->assertTrue(Timezones::isAllowed('Asia/Kolkata'));
        $this->assertFalse(Timezones::isAllowed('Not/A_Zone'));
    }
}