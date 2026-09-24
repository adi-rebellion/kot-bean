@props([
    'product',
    'wireClick' => null,
])

@php
    $price = $product->has_variants
        ? $product->variants->where('is_available', true)->min('price') ?? $product->price
        : $product->price;
    $outOfStock = $product->track_inventory && ! $product->has_variants && $product->stock <= 0;
@endphp

<button
    type="button"
    @if($wireClick && ! $outOfStock) wire:click="{{ $wireClick }}" wire:loading.attr="disabled" @endif
    @disabled($outOfStock)
    {{ $attributes->merge(['class' => 'group relative flex flex-col overflow-hidden rounded-2xl border bg-white text-left transition active:scale-[0.98] ' . ($outOfStock ? 'cursor-not-allowed border-slate-100 opacity-50' : 'border-slate-200/80 shadow-sm hover:border-amber-400 hover:shadow-md')]) }}
>
    <div class="relative aspect-[4/3] w-full overflow-hidden bg-slate-100">
        @if($product->image_url)
            <img src="{{ $product->image_url }}" alt="{{ $product->name }}" class="h-full w-full object-cover" loading="lazy">
        @else
            <div class="flex h-full w-full items-center justify-center bg-gradient-to-br from-slate-50 to-slate-100 text-3xl">☕</div>
        @endif
        @if($outOfStock)
            <div class="absolute inset-0 flex items-center justify-center bg-white/80">
                <span class="rounded-full bg-red-100 px-2 py-1 text-xs font-bold text-red-700">OUT OF STOCK</span>
            </div>
        @elseif($product->track_inventory && ! $product->has_variants)
            <div class="absolute bottom-2 right-2">
                <span @class([
                    'rounded-full px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide',
                    'bg-emerald-500/90 text-white' => $product->stock_status->value === 'in_stock',
                    'bg-amber-500/90 text-white' => in_array($product->stock_status->value, ['low_stock', 'critical']),
                    'bg-red-500/90 text-white' => $product->stock_status->value === 'out_of_stock',
                ])>{{ $product->stock }} left</span>
            </div>
        @endif
    </div>
    <div class="flex flex-col gap-0.5 p-3">
        <h3 class="line-clamp-2 text-sm font-semibold leading-tight text-slate-900">{{ $product->name }}</h3>
        <p class="text-base font-bold text-amber-600">
            @if($product->has_variants)<span class="text-xs font-normal text-slate-400">from </span>@endif
            ₹{{ number_format((float) $price, 0) }}
        </p>
    </div>
    @if($wireClick)
        <div wire:loading.flex wire:target="{{ $wireClick }}" class="absolute inset-0 hidden items-center justify-center bg-white/60 backdrop-blur-[1px]">
            <div class="h-8 w-8 animate-spin rounded-full border-2 border-amber-500 border-t-transparent"></div>
        </div>
    @endif
</button>
