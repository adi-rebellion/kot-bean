<?php

namespace Tests\Feature;

use App\Models\Restaurant;
use App\Models\Vendor;
use Database\Seeders\CafeVendorsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CafeVendorsSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeds_mr_bean_vendors_with_poc_notes(): void
    {
        $restaurant = Restaurant::create([
            'name' => 'Mr Bean Coffee Co.',
            'slug' => 'mr-bean-coffee-co',
        ]);

        (new CafeVendorsSeeder)->run($restaurant);

        $this->assertSame(5, Vendor::query()->where('restaurant_id', $restaurant->id)->count());

        $vandana = Vendor::query()
            ->where('restaurant_id', $restaurant->id)
            ->where('name', 'Vandana Business')
            ->first();

        $this->assertNotNull($vandana);
        $this->assertSame('+91 96696 45684', $vandana->phone);
        $this->assertStringContainsString('Mandal Ji', $vandana->notes ?? '');

        $pio = Vendor::query()
            ->where('restaurant_id', $restaurant->id)
            ->where('name', 'Pio Ice Cubes')
            ->first();

        $this->assertNotNull($pio);
        $this->assertStringContainsString('Lucky', $pio->notes ?? '');
        $this->assertStringContainsString('Akshit', $pio->notes ?? '');
    }
}