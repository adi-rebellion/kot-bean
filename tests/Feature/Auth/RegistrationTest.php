<?php

namespace Tests\Feature\Auth;

use App\Models\Restaurant;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get('/register');

        $response->assertStatus(200)
            ->assertSee('Create your KotBean account')
            ->assertSee('Business name')
            ->assertSee('Already have an account?');
    }

    public function test_new_users_can_register(): void
    {
        $this->seed(PermissionSeeder::class);

        $response = $this->post('/register', [
            'name' => 'Test User',
            'business_name' => 'Sunrise Café',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));
        $this->assertDatabaseHas('restaurants', ['name' => 'Sunrise Café']);

        $restaurant = Restaurant::query()->where('name', 'Sunrise Café')->first();
        $this->assertTrue(auth()->user()->belongsToRestaurant($restaurant->id));
        $this->assertSame($restaurant->id, auth()->user()->restaurant_id);
        $this->assertTrue(auth()->user()->isOwner());
    }
}
