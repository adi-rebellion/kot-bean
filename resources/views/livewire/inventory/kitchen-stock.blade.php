<div class="space-y-4 sm:space-y-6">
    @if (session('kitchen_success'))
        <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">{{ session('kitchen_success') }}</div>
    @endif

    @if ($restockAlerts->isNotEmpty())
        <div class="space-y-3">
            @foreach ($restockAlerts as $alert)
                <div wire:key="alert-{{ $alert['stock_item']->id }}" class="rounded-xl border border-amber-200 bg-amber-50 p-4">
                    <p class="text-sm font-medium text-amber-950">{{ $alert['message'] }}</p>
                    @if ($alert['best_vendor'])
                        @php $vendor = $alert['best_vendor']->vendor; $tel = $kitchenStockService->telLink($vendor); @endphp
                        <div class="mt-3 flex flex-wrap items-center gap-2">
                            <span class="rounded-full bg-white px-3 py-1 text-xs font-semibold text-slate-700">
                                Best price: {{ $vendor->name }} — ₹{{ number_format((float) $alert['best_vendor']->unit_price, 2) }}
                                @if ($alert['best_vendor']->price_unit)
                                    <span class="font-normal text-slate-500">({{ $alert['best_vendor']->price_unit }})</span>
                                @endif
                            </span>
                            @if ($tel)
                                <a href="{{ $tel }}" class="inline-flex items-center gap-1.5 rounded-lg bg-slate-900 px-3 py-1.5 text-xs font-bold text-white hover:bg-slate-800">
                                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                                    Call {{ $vendor->name }}
                                </a>
                            @endif
                        </div>
                    @else
                        <p class="mt-2 text-xs text-amber-800">Add supplier prices below to compare and call vendors.</p>
                    @endif
                </div>
            @endforeach
        </div>
    @endif

    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <p class="text-sm text-slate-600">Track fridge &amp; prep stock with a daily ledger — opening count, restocks, and what’s left at close.</p>
        @if ($canManage)
            <button wire:click="openItemForm()" type="button" class="shrink-0 rounded-lg bg-amber-500 px-4 py-2 text-sm font-semibold text-white hover:bg-amber-600">Add ingredient</button>
        @endif
    </div>

    @if ($showItemForm)
        <form wire:submit="saveItem" class="space-y-4 rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
            <h3 class="font-semibold text-slate-900">{{ $editingItemId ? 'Edit ingredient' : 'New ingredient' }}</h3>
            <div class="grid gap-3 sm:grid-cols-3">
                <div class="sm:col-span-2">
                    <label class="mb-1 block text-xs font-medium text-slate-600">Name</label>
                    <input wire:model="itemName" type="text" placeholder="e.g. Cheese slices" class="w-full rounded-lg border-slate-300 text-sm">
                    @error('itemName') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="mb-1 block text-xs font-medium text-slate-600">Unit</label>
                    <input wire:model="itemUnit" type="text" placeholder="slices, kg, L" class="w-full rounded-lg border-slate-300 text-sm">
                </div>
                <div>
                    <label class="mb-1 block text-xs font-medium text-slate-600">Low-stock warning at</label>
                    <input wire:model="itemMinQuantity" type="number" step="0.01" min="0" placeholder="Optional" class="w-full rounded-lg border-slate-300 text-sm">
                </div>
            </div>
            <div class="flex gap-2">
                <button type="submit" class="rounded-lg bg-slate-800 px-4 py-2 text-sm font-semibold text-white">Save</button>
                <button wire:click="cancelItemForm" type="button" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700">Cancel</button>
            </div>
        </form>
    @endif

    @if ($items->isEmpty())
        <x-empty-state title="No kitchen stock yet" description="Add ingredients like cheese slices to start a daily ledger." />
    @else
        <div class="grid gap-4 lg:grid-cols-12">
            <aside class="lg:col-span-4 xl:col-span-3">
                <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
                    <div class="border-b border-slate-100 px-3 py-2 text-xs font-semibold uppercase tracking-wide text-slate-500">Ingredients</div>
                    <ul class="max-h-[420px] overflow-y-auto divide-y divide-slate-100">
                        @foreach ($items as $item)
                            <li wire:key="stock-item-{{ $item->id }}">
                                <button
                                    wire:click="selectItem({{ $item->id }})"
                                    type="button"
                                    @class([
                                        'flex w-full items-center justify-between px-3 py-3 text-left text-sm transition',
                                        'bg-amber-50 font-semibold text-amber-900' => $activeItemId === $item->id,
                                        'hover:bg-slate-50 text-slate-800' => $activeItemId !== $item->id,
                                    ])
                                >
                                    <span>{{ $item->name }}</span>
                                    <span class="text-xs text-slate-500">{{ number_format((float) $item->current_quantity, 0) }} {{ $item->unit }}</span>
                                </button>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </aside>

            @if ($activeItem)
                <div class="space-y-4 lg:col-span-8 xl:col-span-9">
                    <div class="flex flex-wrap items-start justify-between gap-2">
                        <div>
                            <h2 class="text-lg font-bold text-slate-900">{{ $activeItem->name }}</h2>
                            <p class="text-sm text-slate-500">In fridge now: <span class="font-semibold text-slate-800">{{ number_format((float) $activeItem->current_quantity, 1) }} {{ $activeItem->unit }}</span></p>
                        </div>
                        @if ($canManage)
                            <div class="flex gap-2">
                                <button wire:click="openItemForm({{ $activeItem->id }})" type="button" class="text-sm font-medium text-amber-600 hover:text-amber-700">Edit</button>
                                <button wire:click="deleteItem({{ $activeItem->id }})" wire:confirm="Delete this ingredient and its ledger?" type="button" class="text-sm font-medium text-red-600 hover:text-red-700">Delete</button>
                            </div>
                        @endif
                    </div>

                    @if ($canManage)
                        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                            <h3 class="text-sm font-semibold text-slate-900">Today — {{ today()->format('D, M j') }}</h3>
                            <div class="mt-3 grid gap-3 sm:grid-cols-3">
                                <div>
                                    <label class="mb-1 block text-xs font-medium text-slate-600">Opening (start of day)</label>
                                    <div class="flex gap-2">
                                        <input wire:model="openingQuantity" type="number" step="0.01" min="0" class="w-full rounded-lg border-slate-300 text-sm">
                                        <button wire:click="saveOpening" type="button" class="shrink-0 rounded-lg bg-slate-100 px-3 text-xs font-bold text-slate-700 hover:bg-slate-200">Save</button>
                                    </div>
                                </div>
                                <div>
                                    <label class="mb-1 block text-xs font-medium text-slate-600">Restock (+)</label>
                                    <div class="flex gap-2">
                                        <input wire:model="restockQuantity" type="number" step="0.01" min="0" placeholder="0" class="w-full rounded-lg border-slate-300 text-sm">
                                        <button wire:click="saveRestock" type="button" class="shrink-0 rounded-lg bg-emerald-600 px-3 text-xs font-bold text-white hover:bg-emerald-700">Add</button>
                                    </div>
                                </div>
                                <div>
                                    <label class="mb-1 block text-xs font-medium text-slate-600">Closing (left in fridge)</label>
                                    <div class="flex gap-2">
                                        <input wire:model="closingQuantity" type="number" step="0.01" min="0" class="w-full rounded-lg border-slate-300 text-sm">
                                        <button wire:click="saveClosing" type="button" class="shrink-0 rounded-lg bg-amber-500 px-3 text-xs font-bold text-white hover:bg-amber-600">Close day</button>
                                    </div>
                                </div>
                            </div>
                            <p class="mt-2 text-xs text-slate-500">Used today = opening + restock − closing (saved when you close the day).</p>
                        </div>
                    @endif

                    <div class="rounded-xl border border-slate-200 bg-white shadow-sm">
                        <div class="flex flex-wrap items-center justify-between gap-2 border-b border-slate-100 px-4 py-3">
                            <h3 class="text-sm font-semibold text-slate-900">Supplier prices</h3>
                            @if ($canManage)
                                <button wire:click="openVendorPriceForm()" type="button" class="text-xs font-semibold text-amber-600 hover:text-amber-700">+ Add quote</button>
                            @endif
                        </div>
                        @if ($showVendorPriceForm && $canManage)
                            <form wire:submit="saveVendorPrice" class="border-b border-slate-100 bg-slate-50 p-4">
                                <div class="grid gap-3 sm:grid-cols-3">
                                    <div>
                                        <label class="mb-1 block text-xs font-medium text-slate-600">Supplier</label>
                                        <select wire:model="vendorId" class="w-full rounded-lg border-slate-300 text-sm">
                                            <option value="">Select vendor</option>
                                            @foreach ($vendors as $vendor)
                                                <option value="{{ $vendor->id }}">{{ $vendor->name }}</option>
                                            @endforeach
                                        </select>
                                        @error('vendorId') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                                    </div>
                                    <div>
                                        <label class="mb-1 block text-xs font-medium text-slate-600">Price (₹)</label>
                                        <input wire:model="vendorUnitPrice" type="number" step="0.01" min="0" class="w-full rounded-lg border-slate-300 text-sm">
                                    </div>
                                    <div>
                                        <label class="mb-1 block text-xs font-medium text-slate-600">Per</label>
                                        <input wire:model="vendorPriceUnit" type="text" placeholder="e.g. per kg, per pack" class="w-full rounded-lg border-slate-300 text-sm">
                                    </div>
                                </div>
                                <div class="mt-3 flex gap-2">
                                    <button type="submit" class="rounded-lg bg-slate-800 px-3 py-1.5 text-xs font-bold text-white">Save price</button>
                                    <button wire:click="cancelVendorPriceForm" type="button" class="rounded-lg border border-slate-300 px-3 py-1.5 text-xs font-semibold text-slate-600">Cancel</button>
                                </div>
                            </form>
                        @endif
                        @if ($vendorPrices->isNotEmpty())
                            <ul class="divide-y divide-slate-100">
                                @foreach ($vendorPrices as $quote)
                                    @php $isBest = $bestQuote && $bestQuote->id === $quote->id; $tel = $kitchenStockService->telLink($quote->vendor); @endphp
                                    <li wire:key="quote-{{ $quote->id }}" class="flex flex-wrap items-center justify-between gap-2 px-4 py-3">
                                        <div>
                                            <span class="font-medium text-slate-900">{{ $quote->vendor->name }}</span>
                                            @if ($isBest)
                                                <span class="ml-2 rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-bold text-emerald-800">Lowest price</span>
                                            @endif
                                            <p class="text-sm text-slate-600">
                                                ₹{{ number_format((float) $quote->unit_price, 2) }}
                                                @if ($quote->price_unit)<span class="text-slate-400">· {{ $quote->price_unit }}</span>@endif
                                            </p>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            @if ($tel)
                                                <a href="{{ $tel }}" class="inline-flex items-center gap-1 rounded-lg border border-slate-200 px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50">
                                                    Call
                                                </a>
                                            @endif
                                            @if ($canManage)
                                                <button wire:click="openVendorPriceForm({{ $quote->id }})" type="button" class="text-xs text-amber-600">Edit</button>
                                                <button wire:click="deleteVendorPrice({{ $quote->id }})" wire:confirm="Remove this quote?" type="button" class="text-xs text-red-600">Delete</button>
                                            @endif
                                        </div>
                                    </li>
                                @endforeach
                            </ul>
                        @else
                            <p class="p-4 text-sm text-slate-500">Add prices from each supplier to compare and call the cheapest.</p>
                        @endif
                    </div>

                    <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
                        <div class="border-b border-slate-100 px-4 py-3">
                            <h3 class="text-sm font-semibold text-slate-900">Ledger</h3>
                        </div>
                        @if ($ledger && $ledger->isNotEmpty())
                            <x-table-scroll>
                                <table class="min-w-full divide-y divide-slate-200 text-sm">
                                    <thead class="bg-slate-50 text-xs uppercase text-slate-500">
                                        <tr>
                                            <th class="px-4 py-2 text-left">Date</th>
                                            <th class="px-4 py-2 text-right">Opening</th>
                                            <th class="px-4 py-2 text-right">Restock</th>
                                            <th class="px-4 py-2 text-right">Closing</th>
                                            <th class="px-4 py-2 text-right">Used</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100">
                                        @foreach ($ledger as $row)
                                            <tr wire:key="ledger-{{ $row->id }}" class="hover:bg-slate-50">
                                                <td class="px-4 py-2 font-medium text-slate-800">{{ $row->record_date->format('D, M j') }}</td>
                                                <td class="px-4 py-2 text-right text-slate-600">{{ number_format((float) $row->opening_quantity, 1) }}</td>
                                                <td class="px-4 py-2 text-right text-emerald-700">{{ $row->restock_quantity > 0 ? '+'.number_format((float) $row->restock_quantity, 1) : '—' }}</td>
                                                <td class="px-4 py-2 text-right text-slate-600">{{ $row->closing_quantity !== null ? number_format((float) $row->closing_quantity, 1) : '—' }}</td>
                                                <td class="px-4 py-2 text-right font-semibold text-amber-700">{{ $row->used_quantity !== null ? number_format((float) $row->used_quantity, 1) : '—' }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </x-table-scroll>
                            <div class="border-t border-slate-100 px-4 py-2">{{ $ledger->links() }}</div>
                        @else
                            <p class="p-4 text-sm text-slate-500">No ledger entries yet. Save today’s opening count to start.</p>
                        @endif
                    </div>
                </div>
            @endif
        </div>
    @endif
</div>
