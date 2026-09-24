<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\RestaurantWorkspaceService;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ForcePublicApplicationUrlTest extends TestCase
{
    use RefreshDatabase;

    public function test_pos_livewire_update_uri_uses_the_public_forwarded_host(): void
    {
        $this->seed(PermissionSeeder::class);

        $user = User::factory()->create();
        app(RestaurantWorkspaceService::class)->createWorkspace('Test Café', $user);

        $this->actingAs($user->fresh())
            ->withHeaders([
                'Host' => 'kotbean-web',
                'X-Forwarded-Host' => 'go.snookeraddabhilai.top',
                'X-Forwarded-Proto' => 'https',
            ])
            ->get('/pos')
            ->assertSee('data-update-uri="https://go.snookeraddabhilai.top/livewire-', false)
            ->assertDontSee('http://kotbean-web/', false)
            ->assertSee('livewireSameOriginPath', false);
    }
}
