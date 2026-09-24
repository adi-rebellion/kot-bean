<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Enums\PromotionRule;
use App\Enums\PromotionType;
use App\Livewire\PosPage;
use App\Livewire\PromotionsIndex;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Permission;
use App\Models\Product;
use App\Models\Promotion;
use App\Models\PromotionRedemption;
use App\Models\Restaurant;
use App\Models\Role;
use App\Models\User;
use App\Services\PaymentService;
use App\Services\PromotionService;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PromotionTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private Restaurant $restaurant;

    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PermissionSeeder::class);

        $this->restaurant = Restaurant::create([
            'name' => 'Test Café',
            'slug' => 'test-cafe',
            'default_tax_rate' => 0,
            'settings' => ['tables_enabled' => false],
        ]);

        $role = Role::create([
            'restaurant_id' => $this->restaurant->id,
            'slug' => 'owner',
            'name' => 'Owner',
            'is_system' => true,
        ]);
        $role->permissions()->sync(Permission::pluck('id'));

        $this->owner = User::factory()->create([
            'restaurant_id' => $this->restaurant->id,
            'role_id' => $role->id,
        ]);

        $category = Category::create([
            'restaurant_id' => $this->restaurant->id,
            'name' => 'Coffee',
            'slug' => 'coffee',
        ]);

        $this->product = Product::create([
            'restaurant_id' => $this->restaurant->id,
            'category_id' => $category->id,
            'name' => 'Espresso',
            'slug' => 'espresso',
            'price' => 200,
            'cost_price' => 50,
            'stock' => 20,
            'min_stock' => 5,
            'is_available' => true,
        ]);
    }

    public function test_owner_can_create_promotion(): void
    {
        Livewire::actingAs($this->owner)
            ->test(PromotionsIndex::class)
            ->call('create')
            ->set('name', 'Morning 10%')
            ->set('type', PromotionType::Percentage->value)
            ->set('value', '10')
            ->set('autoApply', true)
            ->set('rule', PromotionRule::FirstCustomerDaily->value)
            ->set('maxUses', '5')
            ->call('save')
            ->assertSessionHas('success');

        $this->assertDatabaseHas('promotions', [
            'name' => 'Morning 10%',
            'auto_apply' => true,
            'max_uses' => 5,
            'rule' => PromotionRule::FirstCustomerDaily->value,
        ]);
    }

    public function test_auto_apply_promotion_applies_at_pos(): void
    {
        Promotion::create([
            'restaurant_id' => $this->restaurant->id,
            'name' => 'Auto 10%',
            'type' => PromotionType::Percentage,
            'value' => 10,
            'auto_apply' => true,
            'rule' => PromotionRule::None,
            'is_active' => true,
        ]);

        Livewire::actingAs($this->owner)
            ->test(PosPage::class)
            ->call('addProduct', $this->product->id)
            ->assertSet('orderId', fn ($id) => $id !== null);

        $order = Order::first();
        $this->assertNotNull($order->promotion_id);
        $this->assertSame(20.0, (float) $order->discount_amount);
        $this->assertSame(180.0, (float) $order->total);
    }

    public function test_promo_code_can_be_applied_manually(): void
    {
        Promotion::create([
            'restaurant_id' => $this->restaurant->id,
            'name' => 'SAVE50',
            'code' => 'SAVE50',
            'type' => PromotionType::Fixed,
            'value' => 50,
            'auto_apply' => false,
            'rule' => PromotionRule::None,
            'is_active' => true,
        ]);

        Livewire::actingAs($this->owner)
            ->test(PosPage::class)
            ->call('addProduct', $this->product->id)
            ->set('promoCode', 'save50')
            ->call('applyPromoCode')
            ->assertSet('successMessage', 'Applied SAVE50.');

        $order = Order::first();
        $this->assertSame(50.0, (float) $order->discount_amount);
        $this->assertSame(150.0, (float) $order->total);
    }

    public function test_first_customer_daily_rule_allows_only_one_redemption_per_day(): void
    {
        $promotion = Promotion::create([
            'restaurant_id' => $this->restaurant->id,
            'name' => 'First of day',
            'type' => PromotionType::Fixed,
            'value' => 20,
            'auto_apply' => true,
            'rule' => PromotionRule::FirstCustomerDaily,
            'is_active' => true,
        ]);

        PromotionRedemption::create([
            'restaurant_id' => $this->restaurant->id,
            'promotion_id' => $promotion->id,
            'order_id' => Order::create([
                'restaurant_id' => $this->restaurant->id,
                'order_number' => 'ORD-000001',
                'created_by' => $this->owner->id,
                'type' => 'dine_in',
                'status' => OrderStatus::Completed,
                'total' => 100,
            ])->id,
            'discount_amount' => 20,
            'redeemed_at' => now(),
        ]);

        $order = Order::create([
            'restaurant_id' => $this->restaurant->id,
            'order_number' => 'ORD-000002',
            'created_by' => $this->owner->id,
            'type' => 'dine_in',
            'status' => OrderStatus::Draft,
            'subtotal' => 200,
            'tax_amount' => 0,
            'total' => 200,
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $this->product->id,
            'product_name' => 'Espresso',
            'quantity' => 1,
            'unit_price' => 200,
            'tax_rate' => 0,
            'tax_amount' => 0,
            'discount_amount' => 0,
            'total' => 200,
        ]);

        $service = app(PromotionService::class);
        $this->assertNull($service->findBestAutoApplyPromotion($order->fresh('items')));
    }

    public function test_first_order_ever_rule_requires_new_customer(): void
    {
        $promotion = Promotion::create([
            'restaurant_id' => $this->restaurant->id,
            'name' => 'Welcome',
            'type' => PromotionType::Percentage,
            'value' => 15,
            'auto_apply' => true,
            'rule' => PromotionRule::FirstOrderEver,
            'is_active' => true,
        ]);

        $customer = Customer::create([
            'restaurant_id' => $this->restaurant->id,
            'name' => 'Returning Guest',
            'phone' => '9876543210',
            'total_orders' => 2,
        ]);

        $order = Order::create([
            'restaurant_id' => $this->restaurant->id,
            'customer_id' => $customer->id,
            'order_number' => 'ORD-000003',
            'created_by' => $this->owner->id,
            'type' => 'dine_in',
            'status' => OrderStatus::Draft,
            'subtotal' => 200,
            'tax_amount' => 0,
            'total' => 200,
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $this->product->id,
            'product_name' => 'Espresso',
            'quantity' => 1,
            'unit_price' => 200,
            'tax_rate' => 0,
            'tax_amount' => 0,
            'discount_amount' => 0,
            'total' => 200,
        ]);

        $service = app(PromotionService::class);
        $this->assertFalse($service->isEligible($promotion, $order->fresh('items'), $customer));
    }

    public function test_redemption_is_recorded_when_order_completes(): void
    {
        $promotion = Promotion::create([
            'restaurant_id' => $this->restaurant->id,
            'name' => 'Auto 10%',
            'type' => PromotionType::Percentage,
            'value' => 10,
            'auto_apply' => true,
            'rule' => PromotionRule::None,
            'is_active' => true,
        ]);

        $order = Order::create([
            'restaurant_id' => $this->restaurant->id,
            'order_number' => 'ORD-000004',
            'created_by' => $this->owner->id,
            'promotion_id' => $promotion->id,
            'type' => 'dine_in',
            'status' => OrderStatus::Paid,
            'subtotal' => 200,
            'tax_amount' => 0,
            'discount_amount' => 20,
            'total' => 180,
        ]);

        app(PaymentService::class)->completeOrder($order);

        $this->assertDatabaseHas('promotion_redemptions', [
            'promotion_id' => $promotion->id,
            'order_id' => $order->id,
            'discount_amount' => 20,
        ]);

        $this->assertSame(1, $promotion->fresh()->uses_count);
    }
}
