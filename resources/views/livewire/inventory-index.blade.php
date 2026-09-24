<div class="mx-auto max-w-7xl space-y-4 sm:space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <h1 class="text-xl font-bold text-slate-900 sm:text-2xl">Inventory</h1>
        @if ($canManage)
            <button wire:click="openAdjustForm" type="button" class="rounded-lg bg-amber-500 px-4 py-2.5 text-sm font-semibold text-white hover:bg-amber-600">Adjust Stock</button>
        @endif
    </div>

    @if (session('success'))
        <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{{ session('error') }}</div>
    @endif

    @if ($showAdjustForm)
        <form wire:submit="saveAdjustment" class="space-y-4 rounded-xl border border-slate-200 bg-white p-4 shadow-sm sm:p-6">
            <h2 class="text-lg font-semibold text-slate-900">Adjust Stock</h2>
            <div class="grid gap-4 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <label class="mb-1 block text-sm font-medium text-slate-700">Product</label>
                    <select wire:model="productId" class="w-full rounded-lg border-slate-300 text-sm focus:border-amber-500 focus:ring-amber-500">
                        <option value="">Select product</option>
                        @foreach ($products as $product)
                            <option value="{{ $product->id }}">{{ $product->name }} (stock: {{ $product->stock }})</option>
                        @endforeach
                    </select>
                    @error('productId') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700">Type</label>
                    <select wire:model="adjustmentType" class="w-full rounded-lg border-slate-300 text-sm focus:border-amber-500 focus:ring-amber-500">
                        <option value="restock">Restock (+)</option>
                        <option value="wastage">Wastage (−)</option>
                    </select>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700">Quantity</label>
                    <input wire:model="quantity" type="number" min="1" class="w-full rounded-lg border-slate-300 text-sm focus:border-amber-500 focus:ring-amber-500">
                    @error('quantity') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div class="sm:col-span-2">
                    <label class="mb-1 block text-sm font-medium text-slate-700">Reason</label>
                    <input wire:model="reason" type="text" placeholder="Optional note" class="w-full rounded-lg border-slate-300 text-sm focus:border-amber-500 focus:ring-amber-500">
                </div>
            </div>
            <div class="flex gap-2">
                <button type="submit" class="rounded-lg bg-amber-500 px-4 py-2.5 text-sm font-semibold text-white hover:bg-amber-600">Save Adjustment</button>
                <button wire:click="cancelAdjustment" type="button" class="rounded-lg border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700">Cancel</button>
            </div>
        </form>
    @endif

    <div class="grid gap-4 sm:grid-cols-3">
        <x-metric-card label="Tracked Products" :value="number_format($totalProducts)" />
        <x-metric-card label="Low Stock Items" :value="number_format($lowStockProducts->count())" />
        <x-metric-card label="Stock Value" :value="'₹' . number_format((float) $totalStockValue, 2)" />
    </div>

    @if ($lowStockProducts->isNotEmpty())
        <div class="rounded-xl border border-amber-200 bg-amber-50 p-4">
            <h2 class="text-sm font-semibold text-amber-800">Low Stock Alerts</h2>
            <div class="mt-2 flex flex-wrap gap-2">
                @foreach ($lowStockProducts as $product)
                    <span wire:key="alert-{{ $product->id }}" class="inline-flex items-center gap-2 rounded-full bg-white px-3 py-1 text-sm text-slate-700 shadow-sm">
                        {{ $product->name }}
                        <x-stock-badge :status="$product->stock_status" />
                    </span>
                @endforeach
            </div>
        </div>
    @endif

    <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-100 px-4 py-3">
            <h2 class="text-base font-semibold text-slate-900 sm:text-lg">Recent Transactions</h2>
        </div>
        @if ($transactions->isNotEmpty())
            {{-- Mobile cards --}}
            <div class="space-y-3 p-4 md:hidden">
                @foreach ($transactions as $txn)
                    <article wire:key="txn-m-{{ $txn->id }}" class="rounded-lg border border-slate-100 p-3">
                        <div class="flex items-start justify-between gap-2">
                            <p class="font-medium text-slate-900">{{ $txn->product?->name ?? '—' }}</p>
                            <span class="shrink-0 text-xs text-slate-500">{{ $txn->created_at->format('M j, g:i A') }}</span>
                        </div>
                        <div class="mt-2 grid grid-cols-2 gap-2 text-sm">
                            <div>
                                <p class="text-xs text-slate-400">Type</p>
                                <p class="text-slate-700">{{ $txn->type->label() }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-slate-400">Qty</p>
                                <p class="text-slate-700">{{ $txn->quantity }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-slate-400">Stock</p>
                                <p class="text-slate-700">{{ $txn->previous_stock }} → {{ $txn->new_stock }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-slate-400">By</p>
                                <p class="truncate text-slate-700">{{ $txn->user?->name ?? '—' }}</p>
                            </div>
                        </div>
                    </article>
                @endforeach
            </div>

            {{-- Desktop table --}}
            <div class="hidden md:block">
                <x-table-scroll>
                    <table class="min-w-full divide-y divide-slate-200">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase text-slate-500">Date</th>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase text-slate-500">Product</th>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase text-slate-500">Type</th>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase text-slate-500">Qty</th>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase text-slate-500">Stock</th>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase text-slate-500">By</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($transactions as $txn)
                                <tr wire:key="txn-{{ $txn->id }}" class="hover:bg-slate-50">
                                    <td class="px-4 py-3 text-sm text-slate-500">{{ $txn->created_at->format('M j, g:i A') }}</td>
                                    <td class="px-4 py-3 text-sm font-medium text-slate-900">{{ $txn->product?->name ?? '—' }}</td>
                                    <td class="px-4 py-3 text-sm text-slate-600">{{ $txn->type->label() }}</td>
                                    <td class="px-4 py-3 text-sm text-slate-600">{{ $txn->quantity }}</td>
                                    <td class="px-4 py-3 text-sm text-slate-600">{{ $txn->previous_stock }} → {{ $txn->new_stock }}</td>
                                    <td class="px-4 py-3 text-sm text-slate-500">{{ $txn->user?->name ?? '—' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </x-table-scroll>
            </div>
            <div class="border-t border-slate-200 px-4 py-3">{{ $transactions->links() }}</div>
        @else
            <div class="p-6 sm:p-8"><x-empty-state title="No transactions yet" /></div>
        @endif
    </div>
</div>
