<div class="mx-auto max-w-7xl space-y-4 sm:space-y-6">
    @if ($ready)
        {{-- Hero: Today's headline --}}
        <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-slate-900 via-slate-800 to-slate-900 p-6 text-white shadow-xl sm:p-8">
            <div class="absolute -right-8 -top-8 h-40 w-40 rounded-full bg-amber-500/10 blur-2xl"></div>
            <div class="absolute -bottom-12 -left-8 h-48 w-48 rounded-full bg-orange-500/10 blur-2xl"></div>
            <div class="relative flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <p class="text-sm font-medium text-amber-400/90">Today's Performance</p>
                    <h1 class="mt-1 text-2xl font-bold tracking-tight sm:text-3xl lg:text-4xl">
                        How is my restaurant doing today?
                    </h1>
                    <p class="mt-2 text-slate-400">{{ now()->format('l, F j, Y') }} · {{ auth()->user()->restaurant?->name }}</p>
                </div>
                <div class="text-left lg:text-right">
                    <p class="text-sm text-slate-400">Today's Sales</p>
                    <p class="text-3xl font-bold tracking-tight text-white sm:text-4xl lg:text-5xl">
                        ₹{{ number_format($metrics['today_sales'] ?? 0, 0) }}
                    </p>
                    @if (($metrics['sales_change_percent'] ?? 0) != 0)
                        <p @class([
                            'mt-1 text-sm font-medium',
                            'text-emerald-400' => ($metrics['sales_change_percent'] ?? 0) >= 0,
                            'text-red-400' => ($metrics['sales_change_percent'] ?? 0) < 0,
                        ])>
                            {{ ($metrics['sales_change_percent'] ?? 0) >= 0 ? '↑' : '↓' }}
                            {{ abs($metrics['sales_change_percent'] ?? 0) }}% vs yesterday
                            (₹{{ number_format($metrics['yesterday_sales'] ?? 0, 0) }})
                        </p>
                    @endif
                </div>
            </div>
        </div>

        {{-- Key metrics grid --}}
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <x-metric-card label="Orders Today" :value="number_format($metrics['today_orders'] ?? 0)" />
            <x-metric-card label="Completed" :value="number_format($metrics['completed_orders'] ?? 0)" />
            <x-metric-card label="Items Sold" :value="number_format($metrics['items_sold'] ?? 0)" />
            <x-metric-card label="Avg Order Value" :value="'₹' . number_format($metrics['avg_order_value'] ?? 0, 0)" />
            <x-metric-card label="Tax Collected" :value="'₹' . number_format($metrics['today_tax'] ?? 0, 0)" />
            <x-metric-card label="Discounts" :value="'₹' . number_format($metrics['today_discounts'] ?? 0, 0)" />
            <x-metric-card label="Pending Orders" :value="number_format($metrics['pending_orders'] ?? 0)" />
            <x-metric-card label="Low Stock" :value="number_format($metrics['low_stock_count'] ?? 0)" />
        </div>

        {{-- Profit row --}}
        @if(auth()->user()->hasPermission('revenue.view'))
            <div class="grid gap-4 sm:grid-cols-3">
                <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-5">
                    <p class="text-sm font-medium text-emerald-700">Today's Revenue</p>
                    <p class="mt-1 text-2xl font-bold text-emerald-900">₹{{ number_format($metrics['today_sales'] ?? 0, 2) }}</p>
                </div>
                <div class="rounded-xl border border-red-200 bg-red-50 p-5">
                    <p class="text-sm font-medium text-red-700">Today's Expenses</p>
                    <p class="mt-1 text-2xl font-bold text-red-900">₹{{ number_format($metrics['today_expenses'] ?? 0, 2) }}</p>
                </div>
                <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="text-sm font-medium text-slate-500">Gross Profit Today</p>
                    <p class="mt-1 text-2xl font-bold text-slate-900">₹{{ number_format($metrics['gross_profit'] ?? 0, 2) }}</p>
                    <p class="mt-1 text-xs text-slate-400">Sales minus recorded expenses</p>
                </div>
            </div>
        @endif

        {{-- Live order pipeline --}}
        @if(array_sum($orderStatusCounts) > 0)
            <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <h2 class="text-sm font-semibold uppercase tracking-wide text-slate-500">Live Order Pipeline</h2>
                <div class="mt-4 grid grid-cols-2 gap-3 sm:grid-cols-4">
                    <div class="rounded-lg bg-slate-50 p-4 text-center">
                        <p class="text-2xl font-bold text-slate-700">{{ $orderStatusCounts['draft'] ?? 0 }}</p>
                        <p class="text-xs text-slate-500">Draft</p>
                    </div>
                    <div class="rounded-lg bg-amber-50 p-4 text-center">
                        <p class="text-2xl font-bold text-amber-700">{{ $orderStatusCounts['preparing'] ?? 0 }}</p>
                        <p class="text-xs text-amber-600">In Kitchen</p>
                    </div>
                    <div class="rounded-lg bg-emerald-50 p-4 text-center">
                        <p class="text-2xl font-bold text-emerald-700">{{ $orderStatusCounts['ready'] ?? 0 }}</p>
                        <p class="text-xs text-emerald-600">Ready</p>
                    </div>
                    <div class="rounded-lg bg-blue-50 p-4 text-center">
                        <p class="text-2xl font-bold text-blue-700">{{ $orderStatusCounts['payment_pending'] ?? 0 }}</p>
                        <p class="text-xs text-blue-600">Awaiting Payment</p>
                    </div>
                </div>
            </div>
        @endif

        <div class="grid gap-6 lg:grid-cols-3">
            {{-- Sales chart --}}
            <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm lg:col-span-2">
                <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
                    <h2 class="text-lg font-semibold text-slate-900">
                        @if($chartPeriod === 'today') Today's Hourly Sales @elseif($chartPeriod === 'yesterday') Yesterday's Hourly Sales @else Sales Trend @endif
                    </h2>
                    <select wire:model.live="chartPeriod" class="rounded-lg border-slate-300 text-sm focus:border-amber-500 focus:ring-amber-500">
                        <option value="today">Today</option>
                        <option value="yesterday">Yesterday</option>
                        <option value="7d">Last 7 Days</option>
                        <option value="30d">Last 30 Days</option>
                    </select>
                </div>
                @if (! empty($chartData['labels']))
                    <div class="space-y-2">
                        @php $max = max(1, max($chartData['sales'] ?? [1])); @endphp
                        @foreach ($chartData['labels'] as $index => $label)
                            @php
                                $amount = $chartData['sales'][$index] ?? 0;
                                $orderCount = $chartData['orders'][$index] ?? 0;
                            @endphp
                            <div class="flex items-center gap-3">
                                <span class="w-10 shrink-0 text-xs text-slate-500 sm:w-14">{{ $label }}</span>
                                <div class="h-3 flex-1 overflow-hidden rounded-full bg-slate-100">
                                    <div class="h-full rounded-full bg-gradient-to-r from-amber-400 to-orange-500 transition-all" style="width: {{ ($amount / $max) * 100 }}%"></div>
                                </div>
                                <span class="w-16 shrink-0 text-right text-xs font-medium text-slate-700 sm:w-24">
                                    ₹{{ number_format($amount, 0) }}
                                    @if($orderCount > 0)
                                        <span class="text-slate-400">· {{ $orderCount }} orders</span>
                                    @endif
                                </span>
                            </div>
                        @endforeach
                    </div>
                @else
                    <x-empty-state title="No sales yet today" description="Completed orders will show up here in real time." />
                @endif
            </div>

            {{-- Payment breakdown today --}}
            <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="mb-4 text-lg font-semibold text-slate-900">Payments Today</h2>
                @if ($paymentBreakdown->isNotEmpty())
                    <ul class="space-y-4">
                        @foreach ($paymentBreakdown as $payment)
                            @php
                                $method = $payment->method instanceof \App\Enums\PaymentMethod
                                    ? $payment->method
                                    : \App\Enums\PaymentMethod::from($payment->method);
                            @endphp
                            <li wire:key="pay-{{ $method->value }}" class="flex items-center justify-between">
                                <div>
                                    <p class="font-medium text-slate-900">{{ $method->label() }}</p>
                                    <p class="text-sm text-slate-500">{{ $payment->transaction_count }} transactions</p>
                                </div>
                                <span class="text-lg font-bold text-amber-600">₹{{ number_format((float) $payment->total_amount, 0) }}</span>
                            </li>
                        @endforeach
                    </ul>
                @else
                    <x-empty-state title="No payments today" />
                @endif
            </div>
        </div>

        <div class="grid gap-6 lg:grid-cols-2">
            {{-- Best sellers TODAY --}}
            <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                <div class="mb-4 flex items-center justify-between">
                    <h2 class="text-lg font-semibold text-slate-900">Most Ordered Today</h2>
                    <span class="rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-semibold text-amber-700">Top sellers</span>
                </div>
                @if ($bestSellers->isNotEmpty())
                    <ul class="divide-y divide-slate-100">
                        @foreach ($bestSellers as $index => $item)
                            <li wire:key="best-{{ $item->product_id }}" class="flex items-center gap-4 py-3">
                                <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-slate-100 text-xs font-bold text-slate-600">{{ $index + 1 }}</span>
                                @if ($item->product?->image_url)
                                    <img src="{{ $item->product->image_url }}" alt="" class="h-12 w-12 shrink-0 rounded-lg object-cover">
                                @else
                                    <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-lg bg-gradient-to-br from-amber-100 to-orange-100 text-lg">☕</div>
                                @endif
                                <div class="min-w-0 flex-1">
                                    <p class="truncate font-medium text-slate-900">{{ $item->product_name }}</p>
                                    <p class="text-sm text-slate-500">{{ $item->total_quantity }} sold · ₹{{ number_format((float) $item->total_revenue, 0) }} revenue</p>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                @else
                    <x-empty-state title="No orders yet today" description="Your best sellers will appear as orders come in." />
                @endif
            </div>

            {{-- Top categories today --}}
            <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="mb-4 text-lg font-semibold text-slate-900">Top Categories Today</h2>
                @if ($topCategories->isNotEmpty())
                    <ul class="space-y-3">
                        @foreach ($topCategories as $cat)
                            <li wire:key="cat-{{ $cat->category_id }}" class="flex items-center justify-between rounded-lg bg-slate-50 px-4 py-3">
                                <div>
                                    <p class="font-medium text-slate-900">{{ $cat->category_name }}</p>
                                    <p class="text-sm text-slate-500">{{ $cat->units_sold }} items sold</p>
                                </div>
                                <span class="font-semibold text-amber-600">₹{{ number_format((float) $cat->revenue, 0) }}</span>
                            </li>
                        @endforeach
                    </ul>
                @else
                    <x-empty-state title="No category data today" />
                @endif
            </div>
        </div>

        {{-- Recent orders today --}}
        <div class="rounded-xl border border-slate-200 bg-white shadow-sm">
            <div class="flex items-center justify-between border-b border-slate-100 px-4 py-4 sm:px-6">
                <h2 class="text-base font-semibold text-slate-900 sm:text-lg">Recent Orders Today</h2>
                <a href="{{ route('orders.index') }}" wire:navigate class="text-sm font-medium text-amber-600 hover:text-amber-700">View all →</a>
            </div>
            @if ($recentOrders->isNotEmpty())
                {{-- Mobile cards --}}
                <div class="space-y-3 p-4 md:hidden">
                    @foreach ($recentOrders as $order)
                        <article wire:key="recent-m-{{ $order->id }}" class="rounded-lg border border-slate-100 bg-slate-50/50 p-3">
                            <div class="flex items-start justify-between gap-2">
                                <div>
                                    <p class="font-semibold text-slate-900">{{ $order->order_number }}</p>
                                    <p class="text-xs text-slate-500">{{ $order->created_at->format('h:i A') }} · {{ $order->table?->name ?? 'No table' }}</p>
                                </div>
                                <x-order-status-badge :status="$order->status" />
                            </div>
                            <div class="mt-2 flex items-center justify-between">
                                <span class="text-sm text-slate-600">{{ $order->items->sum('quantity') }} items</span>
                                <span class="font-semibold text-slate-900">₹{{ number_format((float) $order->total, 2) }}</span>
                            </div>
                        </article>
                    @endforeach
                </div>

                {{-- Desktop table --}}
                <div class="hidden md:block">
                <x-table-scroll>
                    <table class="min-w-full divide-y divide-slate-100">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase text-slate-500">Order</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase text-slate-500">Table</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase text-slate-500">Items</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase text-slate-500">Status</th>
                                <th class="px-6 py-3 text-right text-xs font-medium uppercase text-slate-500">Total</th>
                                <th class="px-6 py-3 text-right text-xs font-medium uppercase text-slate-500">Time</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($recentOrders as $order)
                                <tr wire:key="order-{{ $order->id }}" class="hover:bg-slate-50/50">
                                    <td class="px-6 py-3 text-sm font-medium text-slate-900">{{ $order->order_number }}</td>
                                    <td class="px-6 py-3 text-sm text-slate-600">{{ $order->table?->name ?? '—' }}</td>
                                    <td class="px-6 py-3 text-sm text-slate-600">{{ $order->items->sum('quantity') }} items</td>
                                    <td class="px-6 py-3"><x-order-status-badge :status="$order->status" /></td>
                                    <td class="px-6 py-3 text-right text-sm font-semibold text-slate-900">₹{{ number_format((float) $order->total, 2) }}</td>
                                    <td class="px-6 py-3 text-right text-sm text-slate-500">{{ $order->created_at->format('h:i A') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </x-table-scroll>
                </div>
            @else
                <div class="p-6 sm:p-8"><x-empty-state title="No orders yet today" description="Open POS to take your first order." /></div>
            @endif
        </div>

        {{-- Low stock alerts --}}
        <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="mb-4 flex items-center justify-between">
                <h2 class="text-lg font-semibold text-slate-900">Inventory Alerts</h2>
                @if(($metrics['out_of_stock_count'] ?? 0) > 0)
                    <span class="rounded-full bg-red-100 px-2.5 py-0.5 text-xs font-semibold text-red-700">{{ $metrics['out_of_stock_count'] }} out of stock</span>
                @endif
                <a href="{{ route('inventory.index') }}" wire:navigate class="text-sm font-medium text-amber-600 hover:text-amber-700">Manage →</a>
            </div>
            @if ($lowStockProducts->isNotEmpty())
                <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                    @foreach ($lowStockProducts as $product)
                        <div wire:key="low-{{ $product->id }}" class="flex items-center gap-3 rounded-lg border border-slate-100 p-3">
                            @if ($product->image_url)
                                <img src="{{ $product->image_url }}" alt="" class="h-10 w-10 rounded-lg object-cover">
                            @else
                                <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-slate-100 text-sm">📦</div>
                            @endif
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-medium text-slate-900">{{ $product->name }}</p>
                                <p class="text-xs text-slate-500">{{ $product->stock }} left</p>
                            </div>
                            <x-stock-badge :status="$product->stock_status" />
                        </div>
                    @endforeach
                </div>
            @else
                <x-empty-state title="Inventory is healthy" description="No products are below minimum stock levels." />
            @endif
        </div>
    @else
        <x-empty-state title="No restaurant linked" description="Your account is not linked to a restaurant yet. Contact an administrator." />
    @endif
</div>
