<div
    x-data="{ showExtras: false, cartOpen: false }"
    class="relative flex h-[100dvh] flex-col overflow-hidden bg-slate-50"
>
    {{-- Mobile cart backdrop --}}
    <div
        x-show="cartOpen"
        x-cloak
        @click="cartOpen = false"
        class="fixed inset-0 z-30 bg-slate-900/50 lg:hidden"
    ></div>

    {{-- Top bar --}}
    <header class="z-20 flex shrink-0 flex-wrap items-center gap-2 border-b border-slate-200/80 bg-white px-3 py-2 shadow-sm sm:gap-3 sm:px-4 sm:py-2.5">
        <a href="{{ route('dashboard') }}" wire:navigate class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg text-slate-400 hover:bg-slate-100 hover:text-slate-600" title="Back">
            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        </a>

        @php $posLogo = auth()->user()?->restaurant?->logoUrl(); @endphp
        @if ($posLogo)
            <img src="{{ $posLogo }}" alt="" class="h-9 w-9 shrink-0 rounded-xl border border-slate-200 object-contain bg-white p-0.5">
        @else
            <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-brand text-sm font-bold text-white shadow-sm">{{ strtoupper(substr(auth()->user()?->restaurant?->name ?? 'K', 0, 1)) }}</div>
        @endif

        {{-- Order type --}}
        <div class="scrollbar-hide order-3 flex w-full shrink-0 overflow-x-auto rounded-xl bg-slate-100 p-1 sm:order-none sm:w-auto">
            @foreach (['dine_in' => 'Dine In', 'takeaway' => 'Takeaway', 'delivery' => 'Delivery'] as $value => $label)
                <button
                    wire:key="type-{{ $value }}"
                    wire:click="selectOrderType('{{ $value }}')"
                    type="button"
                    @class([
                        'shrink-0 rounded-lg px-3 py-1.5 text-xs font-semibold transition sm:px-4 sm:text-sm',
                        'bg-white text-slate-900 shadow-sm' => $orderType === $value,
                        'text-slate-500 hover:text-slate-700' => $orderType !== $value,
                    ])
                >{{ $label }}</button>
            @endforeach
        </div>

        {{-- Search --}}
        <div class="relative order-4 min-w-0 w-full flex-1 sm:order-none sm:max-w-lg">
            <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            <input
                wire:model.live.debounce.200ms="search"
                type="search"
                placeholder="Search menu..."
                autofocus
                class="w-full rounded-xl border-0 bg-slate-100 py-2 pl-10 pr-4 text-sm text-slate-900 placeholder:text-slate-400 focus:bg-white focus:ring-2 focus:ring-amber-500/30"
            >
        </div>

        <div class="order-2 ml-auto flex items-center gap-2 sm:order-none">
            @if ($order)
                <span class="hidden rounded-lg bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-600 sm:inline">{{ $order->order_number }}</span>
            @endif
            <button wire:click="startNewOrder" type="button" class="rounded-lg border border-slate-200 px-2.5 py-1.5 text-xs font-medium text-slate-600 hover:bg-slate-50 sm:px-3 sm:text-sm">
                New
            </button>
        </div>
    </header>

    {{-- Toast alerts (pointer-events-none wrapper so it never blocks clicks) --}}
    @if ($errorMessage || $successMessage)
        <div
            x-data="{ show: true }"
            x-show="show"
            x-init="setTimeout(() => show = false, 4000)"
            class="pointer-events-none fixed inset-x-0 top-16 z-[100] flex justify-center px-4"
        >
            @if ($errorMessage)
                <div class="pointer-events-auto flex w-full max-w-md items-center gap-3 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-800 shadow-lg">
                    <span class="flex-1">{{ $errorMessage }}</span>
                    <button wire:click="dismissAlerts" type="button" class="text-red-400 hover:text-red-600">✕</button>
                </div>
            @elseif ($successMessage)
                <div class="pointer-events-auto w-full max-w-md rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800 shadow-lg">
                    {{ $successMessage }}
                </div>
            @endif
        </div>
    @endif

    <div class="relative flex min-h-0 flex-1">
        {{-- Categories --}}
        <aside class="relative z-20 hidden w-36 shrink-0 flex-col border-r border-slate-200/80 bg-white md:flex lg:w-44">
            <nav class="flex-1 overflow-y-auto p-2">
                <button
                    wire:click="selectCategory()"
                    type="button"
                    @class([
                        'mb-1 w-full rounded-xl px-3 py-3 text-left text-sm font-semibold transition',
                        'bg-amber-500 text-white shadow-sm' => ! $selectedCategoryId,
                        'text-slate-600 hover:bg-slate-50' => $selectedCategoryId,
                    ])
                >All</button>
                @foreach ($categories as $category)
                    <button
                        wire:key="cat-{{ $category->id }}"
                        wire:click="selectCategory({{ $category->id }})"
                        type="button"
                        @class([
                            'mb-1 w-full rounded-xl px-3 py-3 text-left text-sm font-semibold transition',
                            'bg-amber-500 text-white shadow-sm' => $selectedCategoryId === $category->id,
                            'text-slate-600 hover:bg-slate-50' => $selectedCategoryId !== $category->id,
                        ])
                    >{{ $category->name }}</button>
                @endforeach
            </nav>
        </aside>

        {{-- Products --}}
        <main class="flex min-w-0 flex-1 flex-col overflow-hidden">
            {{-- Mobile category scroll --}}
            <div class="flex shrink-0 gap-2 overflow-x-auto border-b border-slate-200/80 bg-white p-2 md:hidden">
                <button wire:click="selectCategory()" type="button" @class(['shrink-0 rounded-full px-4 py-2 text-sm font-semibold', 'bg-amber-500 text-white' => ! $selectedCategoryId, 'bg-slate-100 text-slate-600' => $selectedCategoryId])>All</button>
                @foreach ($categories as $category)
                    <button wire:key="mcat-{{ $category->id }}" wire:click="selectCategory({{ $category->id }})" type="button" @class(['shrink-0 rounded-full px-4 py-2 text-sm font-semibold', 'bg-amber-500 text-white' => $selectedCategoryId === $category->id, 'bg-slate-100 text-slate-600' => $selectedCategoryId !== $category->id])>{{ $category->name }}</button>
                @endforeach
            </div>
            <div class="flex-1 overflow-y-auto p-3 pb-24 sm:p-4 lg:pb-4">
                @if ($products->isNotEmpty())
                    <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 2xl:grid-cols-6">
                        @foreach ($products as $product)
                            <x-product-card
                                wire:key="product-{{ $product->id }}"
                                :product="$product"
                                wireClick="addProduct({{ $product->id }})"
                            />
                        @endforeach
                    </div>
                @else
                    <div class="flex h-full items-center justify-center">
                        <x-empty-state title="No products found" description="Try another category or search term." />
                    </div>
                @endif
            </div>
        </main>

        {{-- Cart (slide-over on mobile, sidebar on desktop) --}}
        <aside
            class="fixed inset-y-0 right-0 z-40 flex w-full max-w-[380px] flex-col border-l border-slate-200/80 bg-white shadow-2xl transition-transform duration-200 lg:relative lg:z-20 lg:shrink-0 lg:translate-x-0"
            :class="cartOpen ? 'translate-x-0' : 'translate-x-full lg:translate-x-0'"
        >
            <div class="flex items-center justify-between border-b border-slate-100 px-4 py-3 lg:hidden">
                <h2 class="font-bold text-slate-900">Your Cart</h2>
                <button @click="cartOpen = false" type="button" class="rounded-lg p-2 text-slate-400 hover:bg-slate-100" aria-label="Close cart">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            {{-- Table picker (dine-in, when enabled) --}}
            @if ($tablesEnabled && $orderType === 'dine_in')
                <div class="border-b border-slate-100 p-3">
                    <p class="mb-2 text-xs font-semibold uppercase tracking-wider text-slate-400">Select Table</p>
                    <div class="grid grid-cols-4 gap-1.5">
                        @foreach ($tables as $table)
                            @php
                                $selected = $tableId === $table->id;
                                $statusColor = match($table->status->color()) {
                                    'emerald' => $selected ? 'bg-emerald-500 text-white ring-2 ring-emerald-300' : 'bg-emerald-50 text-emerald-700 hover:bg-emerald-100',
                                    'amber' => $selected ? 'bg-amber-500 text-white ring-2 ring-amber-300' : 'bg-amber-50 text-amber-700 hover:bg-amber-100',
                                    'blue' => $selected ? 'bg-blue-500 text-white ring-2 ring-blue-300' : 'bg-blue-50 text-blue-700 hover:bg-blue-100',
                                    default => $selected ? 'bg-slate-700 text-white' : 'bg-slate-50 text-slate-600 hover:bg-slate-100',
                                };
                            @endphp
                            <button
                                wire:key="tbl-{{ $table->id }}"
                                wire:click="selectTable({{ $table->id }})"
                                type="button"
                                class="rounded-xl py-2.5 text-center text-xs font-bold transition {{ $statusColor }}"
                            >
                                {{ str_replace('Table ', 'T', $table->name) }}
                            </button>
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- Cart header --}}
            <div class="flex items-center justify-between border-b border-slate-100 px-4 py-3">
                <h2 class="font-bold text-slate-900">
                    Current Order
                    @if($order && $order->items->isNotEmpty())
                        <span class="ml-1 text-sm font-normal text-slate-400">({{ $order->items->sum('quantity') }} items)</span>
                    @endif
                </h2>
                <div class="flex items-center gap-2">
                    @if ($order?->customer)
                        <span class="rounded-full bg-blue-100 px-2.5 py-0.5 text-xs font-bold text-blue-800">{{ $order->customer->name }}</span>
                    @endif
                    @if($tablesEnabled && $orderType === 'dine_in' && $tableId)
                        @php $selectedTable = $tables->firstWhere('id', $tableId); @endphp
                        <span class="rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-bold text-amber-800">{{ $selectedTable?->name }}</span>
                    @endif
                </div>
            </div>

            {{-- Cart items --}}
            <div class="flex-1 overflow-y-auto px-3 py-2">
                @if ($order && $order->items->isNotEmpty())
                    <ul class="space-y-2">
                        @foreach ($order->items as $item)
                            <li wire:key="cart-{{ $item->id }}" class="flex items-center gap-3 rounded-xl bg-slate-50 p-3">
                                <div class="flex items-center rounded-lg border border-slate-200 bg-white">
                                    <button wire:click="decrementItem({{ $item->id }})" type="button" class="flex h-9 w-9 items-center justify-center text-lg font-medium text-slate-600 hover:bg-slate-50">−</button>
                                    <span class="min-w-[1.75rem] text-center text-sm font-bold text-slate-900">{{ $item->quantity }}</span>
                                    <button wire:click="incrementItem({{ $item->id }})" type="button" class="flex h-9 w-9 items-center justify-center text-lg font-medium text-slate-600 hover:bg-slate-50">+</button>
                                </div>
                                <div class="min-w-0 flex-1">
                                    <p class="truncate text-sm font-semibold text-slate-900">{{ $item->product_name }}</p>
                                    <p class="text-xs text-slate-500">₹{{ number_format((float) $item->unit_price, 0) }} each</p>
                                </div>
                                <div class="text-right">
                                    <p class="text-sm font-bold text-slate-900">₹{{ number_format((float) $item->total, 0) }}</p>
                                    <button wire:click="removeItem({{ $item->id }})" type="button" class="text-xs text-slate-400 hover:text-red-500">Remove</button>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                @else
                    <div class="flex h-full flex-col items-center justify-center py-12 text-center">
                        <div class="mb-3 flex h-14 w-14 items-center justify-center rounded-2xl bg-slate-100 text-2xl">🛒</div>
                        <p class="font-semibold text-slate-700">Cart is empty</p>
                        <p class="mt-1 text-sm text-slate-400">Tap a product to add it</p>
                    </div>
                @endif
            </div>

            {{-- Totals & actions --}}
            <div class="safe-bottom border-t border-slate-200 bg-slate-50/80 p-4">
                @if ($order && $order->items->isNotEmpty())
                    <div class="mb-4 space-y-1.5 text-sm">
                        <div class="flex justify-between text-slate-500">
                            <span>Subtotal</span>
                            <span>₹{{ number_format((float) $order->subtotal, 2) }}</span>
                        </div>
                        <div class="flex justify-between text-slate-500">
                            <span>Tax</span>
                            <span>₹{{ number_format((float) $order->tax_amount, 2) }}</span>
                        </div>
                        @if ((float) $order->discount_amount > 0)
                            <div class="flex justify-between text-emerald-600">
                                <span>
                                    Discount
                                    @if ($order->promotion)
                                        <span class="text-xs">({{ $order->promotion->name }})</span>
                                    @endif
                                </span>
                                <span>-₹{{ number_format((float) $order->discount_amount, 2) }}</span>
                            </div>
                        @endif
                        <div class="flex justify-between border-t border-slate-200 pt-2 text-xl font-bold text-slate-900">
                            <span>Total</span>
                            <span class="text-amber-600">₹{{ number_format((float) $order->total, 2) }}</span>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-2">
                        <button
                            wire:click="sendToKitchen"
                            wire:loading.attr="disabled"
                            type="button"
                            class="flex items-center justify-center gap-2 rounded-xl border-2 border-slate-800 bg-slate-800 py-3.5 text-sm font-bold text-white transition hover:bg-slate-900 active:scale-[0.98] disabled:opacity-50"
                        >
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                            <span wire:loading.remove wire:target="sendToKitchen">Kitchen</span>
                            <span wire:loading wire:target="sendToKitchen">...</span>
                        </button>
                        <button
                            wire:click="openPaymentModal"
                            wire:loading.attr="disabled"
                            type="button"
                            class="flex items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-amber-500 to-orange-500 py-3.5 text-sm font-bold text-white shadow-lg shadow-amber-500/25 transition hover:from-amber-600 hover:to-orange-600 active:scale-[0.98] disabled:opacity-50"
                        >
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                            <span wire:loading.remove wire:target="openPaymentModal,processPayment">Pay</span>
                            <span wire:loading wire:target="openPaymentModal,processPayment">...</span>
                        </button>
                    </div>

                    <button
                        @click="showExtras = !showExtras"
                        type="button"
                        class="mt-2 w-full py-1 text-xs text-slate-400 hover:text-slate-600"
                        x-text="showExtras ? 'Hide customer & notes ▲' : 'Customer & notes ▼'"
                    ></button>
                    <div x-show="showExtras" x-cloak class="mt-2 space-y-2">
                        <div class="rounded-xl border border-slate-200 bg-white p-3">
                            <p class="mb-2 text-xs font-semibold uppercase tracking-wider text-slate-400">Promo code</p>
                            @if ($order->promotion)
                                <div class="mb-2 flex items-center justify-between rounded-lg bg-emerald-50 px-3 py-2 text-sm text-emerald-700">
                                    <span>{{ $order->promotion->name }} applied</span>
                                    <button wire:click="removePromotion" type="button" class="text-xs font-semibold underline">Remove</button>
                                </div>
                            @endif
                            <div class="flex gap-2">
                                <input wire:model="promoCode" type="text" placeholder="Enter code" class="w-full rounded-xl border-slate-200 text-sm uppercase focus:border-amber-500 focus:ring-amber-500/30">
                                <button wire:click="applyPromoCode" type="button" class="shrink-0 rounded-xl bg-slate-800 px-3 py-2 text-xs font-semibold text-white">Apply</button>
                            </div>
                        </div>
                        <div class="rounded-xl border border-slate-200 bg-white p-3">
                            <p class="mb-2 text-xs font-semibold uppercase tracking-wider text-slate-400">Customer (optional)</p>
                            <div class="space-y-2">
                                <input wire:model.blur="customerName" type="text" placeholder="Customer name" class="w-full rounded-xl border-slate-200 text-sm focus:border-amber-500 focus:ring-amber-500/30">
                                <input wire:model.blur="customerPhone" type="tel" inputmode="tel" placeholder="Mobile number" class="w-full rounded-xl border-slate-200 text-sm focus:border-amber-500 focus:ring-amber-500/30">
                            </div>
                            <p class="mt-2 text-xs text-slate-400">Enter both name and phone to link this order to a customer.</p>
                        </div>
                        <textarea wire:model.blur="orderNotes" rows="2" placeholder="Order notes..." class="w-full rounded-xl border-slate-200 text-sm focus:border-amber-500 focus:ring-amber-500/30"></textarea>
                        @if($orderType === 'delivery')
                            <textarea wire:model.blur="deliveryAddress" rows="2" placeholder="Delivery address" class="w-full rounded-xl border-slate-200 text-sm"></textarea>
                        @endif
                    </div>
                @else
                    <p class="text-center text-sm text-slate-400">Add items to see total</p>
                @endif
            </div>
        </aside>
    </div>

    {{-- Mobile cart bar --}}
    @if ($order && $order->items->isNotEmpty())
        <div class="safe-bottom fixed inset-x-0 bottom-0 z-20 border-t border-slate-200 bg-white p-3 shadow-[0_-4px_20px_rgba(0,0,0,0.08)] lg:hidden">
            <button
                @click="cartOpen = true"
                type="button"
                class="flex w-full items-center justify-between rounded-xl bg-slate-900 px-4 py-3.5 text-white"
            >
                <span class="text-sm font-semibold">{{ $order->items->sum('quantity') }} items · View cart</span>
                <span class="text-lg font-bold text-amber-400">₹{{ number_format((float) $order->total, 0) }}</span>
            </button>
        </div>
    @endif

    {{-- Variant modal --}}
    @if ($showVariantModal)
        <div class="fixed inset-0 z-50 flex items-end justify-center bg-slate-900/50 p-4 sm:items-center" wire:click.self="cancelVariant">
            <div class="w-full max-w-sm rounded-2xl bg-white p-5 shadow-2xl">
                <h3 class="text-lg font-bold text-slate-900">Choose size</h3>
                <div class="mt-3 space-y-2">
                    @foreach ($variantOptions as $variant)
                        <button
                            wire:key="var-{{ $variant->id }}"
                            wire:click="selectVariant({{ $variant->id }})"
                            type="button"
                            @class([
                                'flex w-full items-center justify-between rounded-xl border-2 px-4 py-3 text-left transition',
                                'border-amber-500 bg-amber-50' => $selectedVariantId === $variant->id,
                                'border-slate-200 hover:border-slate-300' => $selectedVariantId !== $variant->id,
                            ])
                        >
                            <span class="font-semibold text-slate-900">{{ $variant->name }}</span>
                            <span class="font-bold text-amber-600">₹{{ number_format((float) $variant->price, 0) }}</span>
                        </button>
                    @endforeach
                </div>
                <div class="mt-4 grid grid-cols-2 gap-2">
                    <button wire:click="cancelVariant" type="button" class="rounded-xl border border-slate-200 py-3 text-sm font-semibold text-slate-600">Cancel</button>
                    <button wire:click="confirmVariant" type="button" class="rounded-xl bg-amber-500 py-3 text-sm font-bold text-white hover:bg-amber-600">Add</button>
                </div>
            </div>
        </div>
    @endif

    {{-- Payment modal --}}
    @if ($showPaymentModal)
        <div class="fixed inset-0 z-50 flex items-end justify-center bg-slate-900/50 p-4 sm:items-center">
            <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-2xl">
                <div class="text-center">
                    <p class="text-sm font-medium text-slate-500">Amount due</p>
                    @if ($order)
                        <p class="mt-1 text-4xl font-bold tracking-tight text-slate-900">₹{{ number_format((float) $order->total, 2) }}</p>
                    @endif
                </div>

                <p class="mb-2 mt-6 text-xs font-semibold uppercase tracking-wider text-slate-400">Payment method</p>
                <div class="grid grid-cols-2 gap-2">
                    @foreach (['cash' => '💵 Cash', 'upi' => '📱 UPI', 'card' => '💳 Card', 'other' => '📝 Other'] as $value => $label)
                        <button
                            wire:key="pay-{{ $value }}"
                            wire:click="selectPaymentMethod('{{ $value }}')"
                            type="button"
                            @class([
                                'rounded-xl border-2 py-4 text-sm font-bold transition',
                                'border-amber-500 bg-amber-50 text-amber-900' => $paymentMethod === $value,
                                'border-slate-200 text-slate-700 hover:border-slate-300' => $paymentMethod !== $value,
                            ])
                        >{{ $label }}</button>
                    @endforeach
                </div>

                @if(in_array($paymentMethod, ['upi', 'card', 'other']))
                    <input wire:model="paymentReference" type="text" placeholder="Reference (optional)" class="mt-3 w-full rounded-xl border-slate-200 text-sm focus:border-amber-500 focus:ring-amber-500/30">
                @endif

                <div class="mt-5 grid grid-cols-2 gap-2">
                    <button wire:click="closePaymentModal" type="button" class="rounded-xl border border-slate-200 py-3.5 text-sm font-semibold text-slate-600">Cancel</button>
                    <button wire:click="processPayment" wire:loading.attr="disabled" type="button" class="rounded-xl bg-gradient-to-r from-amber-500 to-orange-500 py-3.5 text-sm font-bold text-white shadow-lg shadow-amber-500/25 hover:from-amber-600 hover:to-orange-600 disabled:opacity-50">
                        <span wire:loading.remove wire:target="processPayment">Confirm</span>
                        <span wire:loading wire:target="processPayment">Processing...</span>
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
