<?php

namespace Tests\Feature;

use App\Models\Restaurant;
use App\Models\Role;
use App\Models\StockDailyRecord;
use App\Models\StockItem;
use App\Models\User;
use App\Models\Vendor;
use App\Services\KitchenStockService;
use Carbon\Carbon;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KitchenStockTest extends TestCase
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
        ]);

        $role = Role::create([
            'restaurant_id' => $this->restaurant->id,
            'slug' => 'manager',
            'name' => 'Manager',
            'is_system' => true,
        ]);
        $role->givePermissionTo('inventory.manage');
        $role->givePermissionTo('inventory.view');

        $this->user = User::factory()->create([
            'restaurant_id' => $this->restaurant->id,
            'role_id' => $role->id,
        ]);
    }

    public function test_closing_day_calculates_used_quantity_and_updates_current_stock(): void
    {
        $item = StockItem::create([
            'restaurant_id' => $this->restaurant->id,
            'name' => 'Cheese slices',
            'unit' => 'slices',
            'current_quantity' => 30,
        ]);

        $service = app(KitchenStockService::class);
        $service->recordOpening($item, 30, $this->user);
        $service->recordClosing($item, 10, $this->user);

        $record = StockDailyRecord::query()
            ->where('stock_item_id', $item->id)
            ->whereDate('record_date', today())
            ->first();

        $this->assertNotNull($record);
        $this->assertSame('20.00', (string) $record->used_quantity);
        $this->assertSame('10.00', (string) $item->fresh()->current_quantity);
    }

    public function test_restock_alert_when_tomorrow_weekday_usage_exceeds_on_hand_stock(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-07 18:00:00', 'Asia/Kolkata')); // Saturday → tomorrow Sunday

        $item = StockItem::create([
            'restaurant_id' => $this->restaurant->id,
            'name' => 'Cheese slices',
            'unit' => 'slices',
            'current_quantity' => 10,
        ]);

        StockDailyRecord::create([
            'stock_item_id' => $item->id,
            'record_date' => Carbon::parse('2026-11-01'), // previous Sunday
            'opening_quantity' => 50,
            'restock_quantity' => 0,
            'closing_quantity' => 15,
            'used_quantity' => 35,
        ]);

        $alerts = app(KitchenStockService::class)->restockAlerts($this->restaurant);

        $this->assertCount(1, $alerts);
        $this->assertStringContainsString('Sunday', $alerts->first()['message']);
        $this->assertStringContainsString('35', $alerts->first()['message']);
        $this->assertStringContainsString('10', $alerts->first()['message']);

        Carbon::setTestNow();
    }

    public function test_best_vendor_quote_returns_lowest_price(): void
    {
        $item = StockItem::create([
            'restaurant_id' => $this->restaurant->id,
            'name' => 'Cheese slices',
            'unit' => 'slices',
        ]);

        $cheap = Vendor::create([
            'restaurant_id' => $this->restaurant->id,
            'name' => 'Metro Dairy',
            'phone' => '9876543210',
        ]);

        $expensive = Vendor::create([
            'restaurant_id' => $this->restaurant->id,
            'name' => 'Local Market',
            'phone' => '9123456789',
        ]);

        $item->vendorPrices()->create(['vendor_id' => $expensive->id, 'unit_price' => 420]);
        $item->vendorPrices()->create(['vendor_id' => $cheap->id, 'unit_price' => 380]);

        $best = app(KitchenStockService::class)->bestVendorQuote($item->fresh());

        $this->assertNotNull($best);
        $this->assertSame($cheap->id, $best->vendor_id);
        $this->assertSame('tel:+919876543210', app(KitchenStockService::class)->telLink($cheap));
    }
}
