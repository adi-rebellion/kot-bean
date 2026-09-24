@props(['kot'])

@php
    $statusColor = match ($kot->status->color()) {
        'amber' => 'border-amber-400 bg-amber-50',
        'blue' => 'border-blue-400 bg-blue-50',
        'orange' => 'border-orange-400 bg-orange-50',
        'emerald' => 'border-emerald-400 bg-emerald-50',
        'gray' => 'border-slate-400 bg-slate-50',
        'red' => 'border-red-400 bg-red-50',
        default => 'border-slate-300 bg-white',
    };
@endphp

<div {{ $attributes->merge(['class' => "rounded-2xl border-l-4 p-6 shadow-sm {$statusColor}"]) }}>
    <div class="flex items-start justify-between gap-4">
        <div>
            <p class="text-2xl font-bold text-slate-900">{{ $kot->kot_number }}</p>
            <p class="mt-1 text-sm text-slate-600">
                @if($kot->table)
                    Table {{ $kot->table->name }}
                @else
                    {{ $kot->order?->type?->label() ?? 'Order' }}
                @endif
                · {{ $kot->created_at->diffForHumans() }}
            </p>
        </div>
        <span class="rounded-full bg-white px-3 py-1 text-sm font-semibold text-slate-700 shadow-sm">
            {{ $kot->status->label() }}
        </span>
    </div>

    <ul class="mt-4 space-y-2">
        @foreach($kot->items as $item)
            <li wire:key="kot-item-{{ $item->id }}" class="flex items-start justify-between text-lg">
                <span class="font-medium text-slate-900">
                    <span class="mr-2 inline-flex h-7 min-w-[1.75rem] items-center justify-center rounded bg-slate-900 px-2 text-sm font-bold text-white">{{ $item->quantity }}×</span>
                    {{ $item->product_name }}
                    @if($item->variant_name)
                        <span class="text-slate-500">({{ $item->variant_name }})</span>
                    @endif
                </span>
            </li>
            @if($item->notes)
                <li class="ml-10 text-sm italic text-slate-500">{{ $item->notes }}</li>
            @endif
        @endforeach
    </ul>

    @if($kot->notes)
        <p class="mt-3 rounded-lg bg-white/60 px-3 py-2 text-sm text-slate-600">{{ $kot->notes }}</p>
    @endif

    @if($slot->isNotEmpty())
        <div class="mt-4 flex flex-wrap gap-2">{{ $slot }}</div>
    @endif
</div>
