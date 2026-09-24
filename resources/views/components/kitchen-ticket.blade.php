@props([
    'kot',
    'actionLabel',
    'actionClass' => 'bg-amber-500 hover:bg-amber-400',
])

@php
    $elapsedMinutes = (int) $kot->created_at->diffInMinutes(now());
    $isUrgent = $elapsedMinutes >= 15;
    $isWarning = $elapsedMinutes >= 8 && ! $isUrgent;

    $orderType = $kot->order?->type;
    $typeLabel = match ($orderType?->value) {
        'takeaway' => 'Takeaway',
        'delivery' => 'Delivery',
        default => 'Dine In',
    };
    $typeBadgeClass = match ($orderType?->value) {
        'takeaway' => 'bg-sky-500/20 text-sky-300',
        'delivery' => 'bg-violet-500/20 text-violet-300',
        default => 'bg-amber-500/20 text-amber-300',
    };

    $cardBorderClass = match (true) {
        $isUrgent => 'border-red-500/50',
        $isWarning => 'border-orange-500/40',
        $kot->status === \App\Enums\KotStatus::Ready => 'border-emerald-500/40',
        default => 'border-slate-700',
    };
@endphp

<article
    wire:key="kot-{{ $kot->id }}"
    class="flex flex-col overflow-hidden rounded-2xl border bg-slate-900 shadow-lg {{ $cardBorderClass }} {{ $isUrgent && $kot->status === \App\Enums\KotStatus::Pending ? 'animate-pulse' : '' }}"
>
    <div class="border-b border-slate-800 px-4 py-3">
        <div class="flex items-start justify-between gap-3">
            <div class="min-w-0">
                <p class="truncate text-xl font-bold text-white">{{ $kot->kot_number }}</p>
                <p class="mt-0.5 truncate text-xs text-slate-400">{{ $kot->order?->order_number }}</p>
            </div>
            <div @class([
                'shrink-0 rounded-lg px-2.5 py-1 text-xs font-bold tabular-nums',
                'bg-red-500/20 text-red-300' => $isUrgent,
                'bg-orange-500/20 text-orange-300' => $isWarning,
                'bg-slate-800 text-slate-300' => ! $isUrgent && ! $isWarning,
            ])>
                {{ $elapsedMinutes }}m
            </div>
        </div>

        <div class="mt-3 flex flex-wrap items-center gap-2">
            <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $typeBadgeClass }}">
                {{ $typeLabel }}
            </span>
            @if ($kot->table && auth()->user()?->restaurant?->usesTables())
                <span class="inline-flex items-center rounded-full bg-slate-800 px-2.5 py-0.5 text-xs font-semibold text-slate-200">
                    {{ $kot->table->name }}
                </span>
            @endif
            <span class="text-xs text-slate-500">{{ $kot->created_at->format('g:i A') }}</span>
        </div>
    </div>

    <ul class="space-y-2 px-4 py-3">
        @foreach ($kot->items as $item)
            <li wire:key="kot-item-{{ $item->id }}" class="flex items-start gap-3">
                <span class="flex h-8 min-w-[2rem] shrink-0 items-center justify-center rounded-lg bg-white/10 text-sm font-bold">
                    {{ $item->quantity }}×
                </span>
                <div class="min-w-0">
                    <p class="font-semibold text-white">{{ $item->product_name }}</p>
                    @if ($item->variant_name)
                        <p class="text-sm text-slate-400">{{ $item->variant_name }}</p>
                    @endif
                    @if ($item->notes)
                        <p class="mt-1 rounded bg-amber-500/10 px-2 py-1 text-xs text-amber-200">{{ $item->notes }}</p>
                    @endif
                </div>
            </li>
        @endforeach
    </ul>

    @if ($kot->notes)
        <div class="mx-4 mb-3 rounded-lg border border-amber-500/20 bg-amber-500/10 px-3 py-2 text-sm text-amber-100">
            <span class="font-semibold">Note:</span> {{ $kot->notes }}
        </div>
    @endif

    <div class="mt-auto border-t border-slate-800 p-3">
        <button
            wire:click="advanceStatus({{ $kot->id }})"
            wire:loading.attr="disabled"
            wire:target="advanceStatus"
            type="button"
            class="flex w-full items-center justify-center rounded-xl px-4 py-3 text-sm font-bold text-slate-950 transition disabled:opacity-50 {{ $actionClass }}"
        >
            <span wire:loading.remove wire:target="advanceStatus">{{ $actionLabel }}</span>
            <span wire:loading wire:target="advanceStatus">Updating…</span>
        </button>
    </div>
</article>
