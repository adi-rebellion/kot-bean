@props(['status'])

@php
    $statusEnum = $status instanceof \App\Enums\StockStatus
        ? $status
        : \App\Enums\StockStatus::from($status);

    $classes = match ($statusEnum) {
        \App\Enums\StockStatus::InStock => 'bg-emerald-50 text-emerald-700 ring-emerald-600/20',
        \App\Enums\StockStatus::LowStock => 'bg-amber-50 text-amber-700 ring-amber-600/20',
        \App\Enums\StockStatus::Critical => 'bg-orange-50 text-orange-700 ring-orange-600/20',
        \App\Enums\StockStatus::OutOfStock => 'bg-red-50 text-red-700 ring-red-600/20',
    };
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium ring-1 ring-inset {$classes}"]) }}>
    {{ $statusEnum->label() }}
</span>
