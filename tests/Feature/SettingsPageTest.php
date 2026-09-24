<?php

namespace Tests\Feature;

use App\Livewire\SettingsPage;
use App\Models\Restaurant;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class SettingsPageTest extends TestCase
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

        $this->user = User::factory()->create([
            'restaurant_id' => $this->restaurant->id,
            'role_id' => $role->id,
        ]);
    }

    public function test_owner_can_update_theme_color_and_tables_setting(): void
    {
        Livewire::actingAs($this->user)
            ->test(SettingsPage::class)
            ->set('theme_color', '#3b82f6')
            ->set('tables_enabled', false)
            ->call('save')
            ->assertHasNoErrors();

        $this->restaurant->refresh();

        $this->assertSame('#3b82f6', $this->restaurant->themeColor());
        $this->assertFalse($this->restaurant->usesTables());
    }

    public function test_owner_can_upload_business_logo(): void
    {
        Storage::fake('public');

        $logo = UploadedFile::fake()->image('logo.png', 200, 200);

        Livewire::actingAs($this->user)
            ->test(SettingsPage::class)
            ->set('logo', $logo)
            ->call('save')
            ->assertHasNoErrors();

        $this->restaurant->refresh();

        $this->assertNotNull($this->restaurant->logo_path);
        Storage::disk('public')->assertExists($this->restaurant->logo_path);
        $this->assertNotNull($this->restaurant->logoUrl());
    }

    public function test_owner_can_remove_business_logo(): void
    {
        Storage::fake('public');

        $path = 'restaurants/'.$this->restaurant->id.'/old-logo.png';
        Storage::disk('public')->put($path, 'fake');
        $this->restaurant->update(['logo_path' => $path]);

        Livewire::actingAs($this->user)
            ->test(SettingsPage::class)
            ->call('removeLogo')
            ->call('save')
            ->assertHasNoErrors();

        $this->restaurant->refresh();

        $this->assertNull($this->restaurant->logo_path);
        Storage::disk('public')->assertMissing($path);
    }
}
