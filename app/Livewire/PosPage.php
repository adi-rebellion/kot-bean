<?php

namespace App\Livewire;

use App\Enums\OrderStatus;
use App\Enums\OrderType;
use App\Enums\PaymentMethod;
use App\Exceptions\InsufficientStockException;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\RestaurantTable;
use App\Services\CustomerService;
use App\Services\OrderService;
use App\Services\PaymentService;
use App\Services\PromotionService;
use App\Services\TableService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use InvalidArgumentException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.pos')]
#[Title('Point of Sale')]
class PosPage extends Component
{
    public ?int $orderId = null;

    public string $orderType = 'dine_in';

    public ?int $tableId = null;

    public string $search = '';

    public ?int $selectedCategoryId = null;

    public bool $showPaymentModal = false;

    public bool $showVariantModal = false;

    public string $paymentMethod = 'cash';

    public string $paymentAmount = '';

    public ?string $paymentReference = null;

    public ?int $variantProductId = null;

    public ?int $selectedVariantId = null;

    public string $errorMessage = '';

    public string $successMessage = '';

    public string $customerName = '';

    public string $customerPhone = '';

    public string $deliveryAddress = '';

    public string $deliveryPhone = '';

    public string $orderNotes = '';

    public string $promoCode = '';

    /** @var Collection<int, Product> */
    public Collection $variantOptions;

    protected OrderService $orderService;

    protected PaymentService $paymentService;

    protected PromotionService $promotionService;

    public function boot(
        OrderService $orderService,
        PaymentService $paymentService,
        PromotionService $promotionService,
    ): void {
        $this->orderService = $orderService;
        $this->paymentService = $paymentService;
        $this->promotionService = $promotionService;
    }

    public function mount(?int $order = null): void
    {
        $this->variantOptions = collect();

        if ($order) {
            $existing = Order::with('items')->find($order);

            if ($existing && $existing->status === OrderStatus::Draft) {
                $existing->loadMissing('customer');
                $this->orderId = $existing->id;
                $this->orderType = $existing->type->value;
                $this->tableId = $existing->restaurant_table_id;
                $this->customerName = $existing->customer?->name ?? '';
                $this->customerPhone = $existing->customer?->phone ?? '';
                $this->deliveryAddress = $existing->delivery_address ?? '';
                $this->deliveryPhone = $existing->delivery_phone ?? '';
                $this->orderNotes = $existing->notes ?? '';
            }
        }

        if (! $this->restaurantUsesTables()) {
            $this->tableId = null;
        }
    }

    public function updatedOrderType(): void
    {
        $this->syncOrderMeta();

        if ($this->orderType !== 'dine_in') {
            $this->tableId = null;
        }
    }

    public function updatedTableId(): void
    {
        $this->syncOrderMeta();
    }

    public function updatedCustomerName(): void
    {
        $this->syncOrderMeta();
    }

    public function updatedCustomerPhone(): void
    {
        $this->syncOrderMeta();
        $this->refreshPromotions();
    }

    public function applyPromoCode(): void
    {
        $code = strtoupper(trim($this->promoCode));

        if ($code === '') {
            $this->errorMessage = 'Enter a promo code.';

            return;
        }

        try {
            $order = $this->getOrCreateOrder();
            $promotion = $this->promotionService->findByCode($order->restaurant_id, $code);

            if (! $promotion) {
                $this->errorMessage = 'Promo code not found.';

                return;
            }

            $customer = $this->resolveCustomerForPromotion($order);
            $this->promotionService->applyToOrder($order, $promotion, $customer);
            $this->promoCode = '';
            $this->errorMessage = '';
            $this->successMessage = "Applied {$promotion->name}.";
        } catch (InvalidArgumentException $e) {
            $this->errorMessage = $e->getMessage();
        }
    }

    public function removePromotion(): void
    {
        try {
            $order = $this->getCurrentOrder();

            if (! $order) {
                return;
            }

            $this->promotionService->removeFromOrder($order);
            $this->promoCode = '';
            $this->errorMessage = '';
            $this->successMessage = 'Promotion removed.';
        } catch (InvalidArgumentException $e) {
            $this->errorMessage = $e->getMessage();
        }
    }

    public function selectTable(int $tableId): void
    {
        $this->tableId = $this->tableId === $tableId ? null : $tableId;
        $this->syncOrderMeta();
        $this->errorMessage = '';
    }

    public function selectCategory(?int $categoryId = null): void
    {
        $this->selectedCategoryId = $categoryId;
        $this->errorMessage = '';
    }

    public function selectOrderType(string $type): void
    {
        $this->orderType = $type;
        $this->updatedOrderType();
        $this->errorMessage = '';
    }

    public function selectVariant(int $variantId): void
    {
        $this->selectedVariantId = $variantId;
    }

    public function dismissAlerts(): void
    {
        $this->errorMessage = '';
        $this->successMessage = '';
    }

    public function addProduct(int $productId): void
    {
        $product = Product::with('variants')->findOrFail($productId);

        if (! $product->isOrderable()) {
            $this->errorMessage = "{$product->name} is out of stock or unavailable.";

            return;
        }

        if ($product->has_variants && $product->variants->where('is_available', true)->isNotEmpty()) {
            $this->variantProductId = $productId;
            $this->variantOptions = $product->variants->where('is_available', true)->values();
            $this->selectedVariantId = $product->variants->firstWhere('is_default', true)?->id
                ?? $this->variantOptions->first()?->id;
            $this->showVariantModal = true;

            return;
        }

        $this->addProductToCart($productId, null);
    }

    public function confirmVariant(): void
    {
        if ($this->variantProductId && $this->selectedVariantId) {
            $this->addProductToCart($this->variantProductId, $this->selectedVariantId);
        }

        $this->showVariantModal = false;
        $this->variantProductId = null;
        $this->selectedVariantId = null;
        $this->variantOptions = collect();
    }

    public function cancelVariant(): void
    {
        $this->showVariantModal = false;
        $this->variantProductId = null;
        $this->selectedVariantId = null;
        $this->variantOptions = collect();
    }

    public function incrementItem(int $itemId): void
    {
        $this->modifyItemQuantity($itemId, 1);
    }

    public function decrementItem(int $itemId): void
    {
        $item = $this->findCartItem($itemId);

        if ($item->quantity <= 1) {
            $this->removeItem($itemId);

            return;
        }

        $this->modifyItemQuantity($itemId, -1);
    }

    public function removeItem(int $itemId): void
    {
        try {
            $item = $this->findCartItem($itemId);
            $this->orderService->removeItem($item);
            $this->errorMessage = '';
            $this->refreshPromotions();
        } catch (InvalidArgumentException $e) {
            $this->errorMessage = $e->getMessage();
        }
    }

    public function saveDraft(): void
    {
        try {
            $this->syncOrderMeta();
            $this->successMessage = 'Draft saved successfully.';
            $this->errorMessage = '';
        } catch (\Throwable $e) {
            $this->errorMessage = $e->getMessage();
        }
    }

    public function sendToKitchen(): void
    {
        try {
            $order = $this->getCurrentOrder();

            if (! $order) {
                $this->errorMessage = 'Add items to the cart before sending to kitchen.';

                return;
            }

            if ($order->status === OrderStatus::Draft) {
                $order = $this->orderService->confirm($order);
            }

            if ($this->requiresTableSelection() && ! $this->tableId) {
                $this->errorMessage = 'Select a table before sending to kitchen.';

                return;
            }

            $this->orderService->sendToKitchen($order, auth()->user());
            $this->successMessage = 'Sent to kitchen!';
            $this->errorMessage = '';
            $this->startNewOrder();
        } catch (InvalidArgumentException $e) {
            $this->errorMessage = $e->getMessage();
        }
    }

    public function openPaymentModal(): void
    {
        $order = $this->getCurrentOrder();

        if (! $order || $order->items->isEmpty()) {
            $this->errorMessage = 'Add items to the cart first.';

            return;
        }

        if ($this->requiresTableSelection() && ! $this->tableId) {
            $this->errorMessage = 'Select a table before collecting payment.';

            return;
        }

        if ($order->status === OrderStatus::Draft) {
            try {
                $order = $this->orderService->confirm($order);
            } catch (InvalidArgumentException $e) {
                $this->errorMessage = $e->getMessage();

                return;
            }
        }

        $this->paymentAmount = number_format((float) $order->total, 2, '.', '');
        $this->paymentMethod = PaymentMethod::Cash->value;
        $this->paymentReference = null;
        $this->showPaymentModal = true;
    }

    public function selectPaymentMethod(string $method): void
    {
        $this->paymentMethod = $method;
    }

    public function processPayment(): void
    {
        $this->validate([
            'paymentMethod' => 'required|in:cash,upi,card,other',
            'paymentAmount' => 'required|numeric|min:0.01',
        ]);

        try {
            $order = Order::findOrFail($this->orderId);

            $this->paymentService->recordPayment(
                $order,
                PaymentMethod::from($this->paymentMethod),
                (float) $this->paymentAmount,
                auth()->user(),
                $this->paymentReference,
            );

            $this->paymentService->completeOrder($order->fresh());
            $this->showPaymentModal = false;
            $this->successMessage = 'Payment complete!';
            $this->errorMessage = '';
            $this->startNewOrder();
        } catch (InvalidArgumentException $e) {
            $this->errorMessage = $e->getMessage();
        }
    }

    public function closePaymentModal(): void
    {
        $this->showPaymentModal = false;
    }

    public function startNewOrder(): void
    {
        $this->orderId = null;
        $this->customerName = '';
        $this->customerPhone = '';
        $this->deliveryAddress = '';
        $this->deliveryPhone = '';
        $this->orderNotes = '';
        $this->promoCode = '';
        $this->errorMessage = '';
        $this->showPaymentModal = false;
        $this->showVariantModal = false;
    }

    public function render(): View
    {
        $restaurant = auth()->user()->restaurant;

        $categories = Category::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        $productsQuery = Product::query()
            ->with(['category', 'variants'])
            ->where('is_available', true)
            ->orderBy('sort_order');

        if ($this->selectedCategoryId) {
            $productsQuery->where('category_id', $this->selectedCategoryId);
        }

        if ($this->search !== '') {
            $productsQuery->where(function ($query) {
                $query->where('name', 'like', '%'.$this->search.'%')
                    ->orWhere('sku', 'like', '%'.$this->search.'%');
            });
        }

        $products = $productsQuery->get();
        $tablesEnabled = $this->restaurantUsesTables();
        $tables = $tablesEnabled
            ? RestaurantTable::query()->orderBy('sort_order')->get()
            : collect();
        $order = $this->getCurrentOrder();

        return view('livewire.pos-page', [
            'categories' => $categories,
            'products' => $products,
            'tables' => $tables,
            'tablesEnabled' => $tablesEnabled,
            'order' => $order,
        ]);
    }

    private function addProductToCart(int $productId, ?int $variantId): void
    {
        try {
            $order = $this->getOrCreateOrder();
            $product = Product::findOrFail($productId);
            $variant = $variantId ? ProductVariant::findOrFail($variantId) : null;

            $existing = $order->items()
                ->where('product_id', $productId)
                ->when($variantId, fn ($q) => $q->where('product_variant_id', $variantId))
                ->when(! $variantId, fn ($q) => $q->whereNull('product_variant_id'))
                ->first();

            if ($existing) {
                $this->orderService->updateItemQuantity($existing, $existing->quantity + 1);
            } else {
                $this->orderService->addItem($order, $product, 1, $variant);
            }

            $this->errorMessage = '';
            $this->successMessage = '';
            $this->refreshPromotions($order->fresh(['items', 'customer', 'promotion']));
            $this->dispatch('item-added');
        } catch (InsufficientStockException $e) {
            $name = $e->variant
                ? "{$e->product->name} ({$e->variant->name})"
                : $e->product->name;
            $this->errorMessage = "Not enough stock for {$name}. Only {$e->available} available.";
        } catch (InvalidArgumentException $e) {
            $this->errorMessage = $e->getMessage();
        }
    }

    private function modifyItemQuantity(int $itemId, int $delta): void
    {
        try {
            $item = $this->findCartItem($itemId);
            $newQty = $item->quantity + $delta;
            $this->orderService->updateItemQuantity($item, $newQty);
            $this->errorMessage = '';
            $this->refreshPromotions();
        } catch (InsufficientStockException $e) {
            $name = $e->variant
                ? "{$e->product->name} ({$e->variant->name})"
                : $e->product->name;
            $this->errorMessage = "Not enough stock for {$name}. Only {$e->available} available.";
        } catch (InvalidArgumentException $e) {
            $this->errorMessage = $e->getMessage();
        }
    }

    private function refreshPromotions(?Order $order = null): void
    {
        $order ??= $this->getCurrentOrder();

        if (! $order || $order->status !== OrderStatus::Draft) {
            return;
        }

        $order = $order->fresh(['items', 'promotion']);
        $customer = $this->resolveCustomerForPromotion($order);

        if ($order->promotion_id && ! $order->promotion?->auto_apply) {
            if ($this->promotionService->isEligible($order->promotion, $order, $customer)) {
                $discount = $this->promotionService->calculateDiscountAmount($order->promotion, $order);

                if ((float) $order->discount_amount !== $discount) {
                    $order->update(['discount_amount' => $discount]);
                    $this->orderService->recalculateTotals($order);
                }
            } else {
                $this->promotionService->removeFromOrder($order);
            }

            return;
        }

        $this->promotionService->syncAutoApply($order, $customer);
    }

    private function resolveCustomerForPromotion(Order $order): ?Customer
    {
        $order->loadMissing('customer');

        if ($order->customer_id) {
            return $order->customer;
        }

        $name = trim($this->customerName);
        $phone = trim($this->customerPhone);

        if ($name === '' || $phone === '') {
            return null;
        }

        return app(CustomerService::class)->findOrCreate($order->restaurant_id, $name, $phone);
    }

    private function findCartItem(int $itemId): OrderItem
    {
        $order = $this->getOrCreateOrder();

        return $order->items()->findOrFail($itemId);
    }

    private function getOrCreateOrder(): Order
    {
        if ($this->orderId) {
            $order = Order::with('items.product', 'items.variant')->findOrFail($this->orderId);
            $this->syncOrderMeta($order);

            return $order->fresh(['items.product', 'items.variant']);
        }

        $order = $this->orderService->createDraft([
            'type' => OrderType::from($this->orderType),
            'restaurant_table_id' => $this->resolveTableId(),
            'customer_id' => $this->resolveCustomerId(auth()->user()->restaurant_id),
            'delivery_address' => $this->deliveryAddress ?: null,
            'delivery_phone' => $this->resolveDeliveryPhone(),
            'notes' => $this->orderNotes ?: null,
        ], auth()->user());

        $this->orderId = $order->id;

        return $order->load(['items.product', 'items.variant']);
    }

    private function getCurrentOrder(): ?Order
    {
        if (! $this->orderId) {
            return null;
        }

        return Order::with(['items', 'customer', 'promotion'])->find($this->orderId);
    }

    private function syncOrderMeta(?Order $order = null): void
    {
        $order ??= $this->orderId ? Order::find($this->orderId) : null;

        if (! $order || $order->status !== OrderStatus::Draft) {
            return;
        }

        $order->update([
            'type' => OrderType::from($this->orderType),
            'restaurant_table_id' => $this->resolveTableId(),
            'customer_id' => $this->resolveCustomerId($order->restaurant_id),
            'delivery_address' => $this->deliveryAddress ?: null,
            'delivery_phone' => $this->resolveDeliveryPhone(),
            'notes' => $this->orderNotes ?: null,
        ]);

        if ($this->requiresTableSelection() && $this->tableId) {
            $table = RestaurantTable::find($this->tableId);
            if ($table) {
                app(TableService::class)->assignOrder($table, $order);
            }
        }
    }

    private function restaurantUsesTables(): bool
    {
        return auth()->user()?->restaurant?->usesTables() ?? true;
    }

    private function requiresTableSelection(): bool
    {
        return $this->restaurantUsesTables() && $this->orderType === 'dine_in';
    }

    private function resolveTableId(): ?int
    {
        return $this->requiresTableSelection() ? $this->tableId : null;
    }

    private function resolveCustomerId(int $restaurantId): ?int
    {
        $name = trim($this->customerName);
        $phone = trim($this->customerPhone);

        if ($name === '' || $phone === '') {
            return null;
        }

        return app(CustomerService::class)->findOrCreate($restaurantId, $name, $phone)->id;
    }

    private function resolveDeliveryPhone(): ?string
    {
        if ($this->deliveryPhone !== '') {
            return $this->deliveryPhone;
        }

        $phone = trim($this->customerPhone);

        return $phone !== '' ? $phone : null;
    }
}
