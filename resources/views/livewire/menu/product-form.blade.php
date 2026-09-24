<div
    @if ($generationPending) wire:poll.2s="checkGenerationStatus" @endif
    class="mx-auto max-w-3xl space-y-4 sm:space-y-6"
>
    <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
        <h1 class="text-xl font-bold text-slate-900 sm:text-2xl">{{ $product?->exists ? 'Edit Product' : 'Create Product' }}</h1>
        <a href="{{ route('menu.index') ?? '#' }}" wire:navigate class="text-sm font-medium text-slate-600 hover:text-slate-900">← Back to products</a>
    </div>

    @if (session('success'))
        <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">{{ session('success') }}</div>
    @endif

    <form wire:submit="save" class="space-y-6 rounded-xl border border-slate-200 bg-white p-4 shadow-sm sm:p-6">
        <div class="grid gap-4 sm:grid-cols-2">
            <div class="sm:col-span-2">
                <label class="mb-1 block text-sm font-medium text-slate-700">Name</label>
                <input wire:model="name" type="text" class="w-full rounded-lg border-slate-300 text-sm focus:border-amber-500 focus:ring-amber-500">
                @error('name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium text-slate-700">Category</label>
                <select wire:model="category_id" class="w-full rounded-lg border-slate-300 text-sm focus:border-amber-500 focus:ring-amber-500">
                    <option value="">Select category</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->id }}">{{ $category->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium text-slate-700">SKU</label>
                <input wire:model="sku" type="text" class="w-full rounded-lg border-slate-300 text-sm focus:border-amber-500 focus:ring-amber-500">
            </div>
            <div class="sm:col-span-2">
                <label class="mb-1 block text-sm font-medium text-slate-700">Description</label>
                <textarea wire:model="description" rows="3" class="w-full rounded-lg border-slate-300 text-sm focus:border-amber-500 focus:ring-amber-500"></textarea>
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium text-slate-700">Price (₹)</label>
                <input wire:model="price" type="number" step="0.01" min="0" class="w-full rounded-lg border-slate-300 text-sm focus:border-amber-500 focus:ring-amber-500">
                @error('price') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium text-slate-700">Cost Price (₹)</label>
                <input wire:model="cost_price" type="number" step="0.01" min="0" class="w-full rounded-lg border-slate-300 text-sm focus:border-amber-500 focus:ring-amber-500">
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium text-slate-700">Tax Rate (%)</label>
                <input wire:model="tax_rate" type="number" step="0.01" min="0" max="100" class="w-full rounded-lg border-slate-300 text-sm focus:border-amber-500 focus:ring-amber-500">
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium text-slate-700">Prep Time (min)</label>
                <input wire:model="preparation_time" type="number" min="0" class="w-full rounded-lg border-slate-300 text-sm focus:border-amber-500 focus:ring-amber-500">
            </div>
            @if ($track_inventory)
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700">Stock</label>
                    <input wire:model="stock" type="number" min="0" class="w-full rounded-lg border-slate-300 text-sm focus:border-amber-500 focus:ring-amber-500">
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700">Min Stock</label>
                    <input wire:model="min_stock" type="number" min="0" class="w-full rounded-lg border-slate-300 text-sm focus:border-amber-500 focus:ring-amber-500">
                </div>
            @endif
            <div>
                <label class="mb-1 block text-sm font-medium text-slate-700">Sort Order</label>
                <input wire:model="sort_order" type="number" min="0" class="w-full rounded-lg border-slate-300 text-sm focus:border-amber-500 focus:ring-amber-500">
            </div>
        </div>

        <div class="flex flex-wrap gap-4">
            <label class="flex items-center gap-2 text-sm text-slate-700">
                <input wire:model="track_inventory" type="checkbox" class="rounded border-slate-300 text-amber-600 focus:ring-amber-500">
                Track inventory
            </label>
            <label class="flex items-center gap-2 text-sm text-slate-700">
                <input wire:model="has_variants" type="checkbox" class="rounded border-slate-300 text-amber-600 focus:ring-amber-500">
                Has variants
            </label>
            <label class="flex items-center gap-2 text-sm text-slate-700">
                <input wire:model="is_available" type="checkbox" class="rounded border-slate-300 text-amber-600 focus:ring-amber-500">
                Available for ordering
            </label>
        </div>

        @if ($product?->exists && ($has_variants || $variants->isNotEmpty()))
            <div class="rounded-lg border border-slate-200 bg-slate-50 p-4">
                <h3 class="text-sm font-semibold text-slate-900">Product Variants</h3>
                @if ($variants->isNotEmpty())
                    <ul class="mt-3 space-y-2">
                        @foreach ($variants as $variant)
                            <li wire:key="variant-{{ $variant->id }}" class="flex items-center justify-between rounded-lg bg-white px-3 py-2 text-sm">
                                <div>
                                    <span class="font-semibold text-slate-900">{{ $variant->name }}</span>
                                    <span class="ml-2 text-amber-600">₹{{ number_format((float) $variant->price, 2) }}</span>
                                    @if ($variant->is_default)<span class="ml-2 text-xs text-slate-400">Default</span>@endif
                                    <span class="ml-2 text-xs text-slate-500">Stock: {{ $variant->stock }}</span>
                                </div>
                                <div class="flex gap-2">
                                    <button wire:click="editVariant({{ $variant->id }})" type="button" class="text-amber-600 hover:text-amber-700">Edit</button>
                                    <button wire:click="deleteVariant({{ $variant->id }})" wire:confirm="Delete this variant?" type="button" class="text-red-600 hover:text-red-700">Delete</button>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                @endif
                <div class="mt-4 grid gap-3 sm:grid-cols-2">
                    <div>
                        <label class="mb-1 block text-xs font-medium text-slate-600">Variant name</label>
                        <input wire:model="variantName" type="text" placeholder="e.g. Large" class="w-full rounded-lg border-slate-300 text-sm">
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-medium text-slate-600">Price (₹)</label>
                        <input wire:model="variantPrice" type="number" step="0.01" min="0" class="w-full rounded-lg border-slate-300 text-sm">
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-medium text-slate-600">SKU</label>
                        <input wire:model="variantSku" type="text" class="w-full rounded-lg border-slate-300 text-sm">
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-medium text-slate-600">Stock</label>
                        <input wire:model="variantStock" type="number" min="0" class="w-full rounded-lg border-slate-300 text-sm">
                    </div>
                </div>
                <div class="mt-3 flex flex-wrap gap-4">
                    <label class="flex items-center gap-2 text-sm text-slate-700">
                        <input wire:model="variantIsDefault" type="checkbox" class="rounded border-slate-300 text-amber-600">
                        Default variant
                    </label>
                    <label class="flex items-center gap-2 text-sm text-slate-700">
                        <input wire:model="variantIsAvailable" type="checkbox" class="rounded border-slate-300 text-amber-600">
                        Available
                    </label>
                </div>
                <div class="mt-3 flex gap-2">
                    <button wire:click="saveVariant" type="button" class="rounded-lg bg-slate-800 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-900">{{ $editingVariantId ? 'Update Variant' : 'Add Variant' }}</button>
                    @if ($editingVariantId)
                        <button wire:click="cancelVariantForm" type="button" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700">Cancel</button>
                    @endif
                </div>
            </div>
        @endif

        {{-- Product Image --}}
        <div class="rounded-lg border border-slate-200 bg-slate-50 p-4">
            <h3 class="text-sm font-semibold text-slate-900">Product Image</h3>
            <p class="mt-1 text-xs text-slate-500">Upload a photo or generate one with AI.</p>
            <div class="mt-3 flex flex-col items-start gap-4 sm:flex-row">
                <div class="flex h-24 w-24 shrink-0 items-center justify-center overflow-hidden rounded-lg border border-slate-200 bg-slate-200 text-slate-400">
                    @if ($image && $image->isPreviewable())
                        <img src="{{ $image->temporaryUrl() }}" alt="Product preview" class="h-full w-full object-cover">
                    @elseif (! $removeImage && $product?->image_url)
                        <img src="{{ $product->image_url }}" alt="{{ $product->name }}" class="h-full w-full object-cover">
                    @else
                        <svg class="h-8 w-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16"/></svg>
                    @endif
                </div>
                <div class="min-w-0 flex-1 space-y-2">
                    <input
                        wire:model="image"
                        type="file"
                        accept="image/png,image/jpeg,image/webp"
                        class="block w-full text-sm text-slate-500 file:mr-3 file:rounded-lg file:border-0 file:bg-white file:px-3 file:py-2 file:text-sm file:font-semibold file:text-slate-700 hover:file:bg-slate-100"
                    >
                    <p class="text-xs text-slate-500">PNG, JPG or WebP. Max 8 MB.</p>
                    @if ($image || (! $removeImage && $product?->image_url))
                        <button wire:click.prevent="clearImage" type="button" class="text-xs font-medium text-red-600 hover:text-red-700">Remove image</button>
                    @endif
                    @error('image') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                    <div wire:loading wire:target="image" class="text-xs text-slate-500">Uploading preview…</div>
                    <button
                        wire:click="requestAiImage"
                        wire:loading.attr="disabled"
                        type="button"
                        class="rounded-lg bg-slate-800 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-900 disabled:opacity-50"
                    >
                        <span wire:loading.remove wire:target="requestAiImage">Generate with AI</span>
                        <span wire:loading wire:target="requestAiImage">Requesting...</span>
                    </button>
                    @if ($generationMessage)
                        <p @class([
                            'mt-2 text-sm',
                            'text-amber-600' => $generationPending,
                            'text-emerald-600' => ! $generationPending && (str_contains(strtolower($generationMessage), 'applied') || str_contains(strtolower($generationMessage), 'generated')),
                            'text-red-600' => ! $generationPending && ! str_contains(strtolower($generationMessage), 'applied') && ! str_contains(strtolower($generationMessage), 'generated') && ! str_contains(strtolower($generationMessage), 'enter at least'),
                            'text-slate-600' => ! $generationPending && str_contains(strtolower($generationMessage), 'enter at least'),
                        ])>{{ $generationMessage }}</p>
                    @endif
                </div>
            </div>
        </div>

        <div class="flex justify-end gap-3 border-t border-slate-100 pt-4">
            <button type="submit" wire:loading.attr="disabled" class="rounded-lg bg-amber-500 px-6 py-2.5 text-sm font-bold text-white hover:bg-amber-600 disabled:opacity-50">
                <span wire:loading.remove wire:target="save">{{ $product?->exists ? 'Update Product' : 'Create Product' }}</span>
                <span wire:loading wire:target="save">Saving...</span>
            </button>
        </div>
    </form>
</div>
