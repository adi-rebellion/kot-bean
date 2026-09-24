<div class="mx-auto max-w-7xl space-y-4 sm:space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <h1 class="text-xl font-bold text-slate-900 sm:text-2xl">Products</h1>
        <a href="{{ route('menu.create') ?? '#' }}" wire:navigate class="inline-flex w-full items-center justify-center rounded-lg bg-amber-500 px-4 py-2.5 text-sm font-semibold text-white hover:bg-amber-600 sm:w-auto">
            Add Product
        </a>
    </div>

    <div class="grid gap-3 sm:flex sm:flex-wrap sm:items-center">
        <input wire:model.live.debounce.300ms="search" type="search" placeholder="Search products..." class="w-full rounded-lg border-slate-300 text-sm focus:border-amber-500 focus:ring-amber-500 sm:min-w-[200px] sm:flex-1">
        <select wire:model.live="categoryFilter" class="w-full rounded-lg border-slate-300 text-sm focus:border-amber-500 focus:ring-amber-500 sm:w-auto">
            <option value="">All categories</option>
            @foreach ($categories as $category)
                <option value="{{ $category->id }}">{{ $category->name }}</option>
            @endforeach
        </select>
    </div>

    @if ($products->isNotEmpty())
        {{-- Mobile cards --}}
        <div class="space-y-3 md:hidden">
            @foreach ($products as $product)
                <article wire:key="product-m-{{ $product->id }}" class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                    <div class="flex items-start gap-3">
                        @if ($product->image_url)
                            <img src="{{ $product->image_url }}" alt="" class="h-14 w-14 shrink-0 rounded-lg object-cover">
                        @else
                            <div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-lg bg-slate-100 text-slate-400">
                                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14"/></svg>
                            </div>
                        @endif
                        <div class="min-w-0 flex-1">
                            <div class="flex items-start justify-between gap-2">
                                <div class="min-w-0">
                                    <p class="font-semibold text-slate-900">{{ $product->name }}</p>
                                    <p class="text-sm text-slate-500">{{ $product->category?->name ?? '—' }}</p>
                                </div>
                                <p class="shrink-0 font-bold text-amber-600">₹{{ number_format((float) $product->price, 0) }}</p>
                            </div>
                            <div class="mt-2 flex flex-wrap items-center gap-2">
                                <span @class([
                                    'inline-flex rounded-full px-2 py-0.5 text-xs font-medium',
                                    'bg-emerald-50 text-emerald-700' => $product->is_available,
                                    'bg-slate-100 text-slate-600' => ! $product->is_available,
                                ])>{{ $product->is_available ? 'Available' : 'Unavailable' }}</span>
                                @if ($product->track_inventory)
                                    <span class="text-xs text-slate-500">Stock: {{ $product->stock }}</span>
                                    <x-stock-badge :status="$product->stock_status" />
                                @endif
                            </div>
                            <a href="{{ route('menu.edit', $product) ?? '#' }}" wire:navigate class="mt-2 inline-block text-sm font-medium text-amber-600 hover:text-amber-700">Edit →</a>
                        </div>
                    </div>
                </article>
            @endforeach
        </div>

        {{-- Desktop table --}}
        <div class="hidden overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm md:block">
            <x-table-scroll>
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase text-slate-500">Product</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase text-slate-500">Category</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase text-slate-500">Price</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase text-slate-500">Stock</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase text-slate-500">Status</th>
                            <th class="px-4 py-3 text-right text-xs font-medium uppercase text-slate-500">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($products as $product)
                            <tr wire:key="product-{{ $product->id }}" class="hover:bg-slate-50">
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-3">
                                        @if ($product->image_url)
                                            <img src="{{ $product->image_url }}" alt="" class="h-10 w-10 rounded-lg object-cover">
                                        @else
                                            <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-slate-100 text-slate-400">
                                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14"/></svg>
                                            </div>
                                        @endif
                                        <div>
                                            <p class="text-sm font-semibold text-slate-900">{{ $product->name }}</p>
                                            @if ($product->sku)
                                                <p class="text-xs text-slate-500">{{ $product->sku }}</p>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-sm text-slate-600">{{ $product->category?->name ?? '—' }}</td>
                                <td class="px-4 py-3 text-sm font-semibold text-amber-600">₹{{ number_format((float) $product->price, 2) }}</td>
                                <td class="px-4 py-3 text-sm text-slate-600">
                                    @if ($product->track_inventory)
                                        {{ $product->stock }}
                                        <x-stock-badge :status="$product->stock_status" class="ml-1" />
                                    @else
                                        —
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    <span @class([
                                        'inline-flex rounded-full px-2 py-0.5 text-xs font-medium',
                                        'bg-emerald-50 text-emerald-700' => $product->is_available,
                                        'bg-slate-100 text-slate-600' => ! $product->is_available,
                                    ])>{{ $product->is_available ? 'Available' : 'Unavailable' }}</span>
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <a href="{{ route('menu.edit', $product) ?? '#' }}" wire:navigate class="text-sm font-medium text-amber-600 hover:text-amber-700">Edit</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </x-table-scroll>
        </div>
    @else
        <x-empty-state title="No products found" description="Create your first product to build the menu.">
            <a href="{{ route('menu.create') ?? '#' }}" class="mt-2 inline-flex rounded-lg bg-amber-500 px-4 py-2 text-sm font-semibold text-white hover:bg-amber-600">Add Product</a>
        </x-empty-state>
    @endif
</div>
