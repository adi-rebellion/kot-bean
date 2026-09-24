@php
    $currentRestaurant = auth()->user()?->restaurant;
    $businesses = auth()->user()?->accessibleRestaurants() ?? collect();
@endphp

<div class="relative min-w-0 flex-1" x-data="{ open: false }" @click.outside="open = false">
    <button type="button" @click="open = !open" class="flex w-full min-w-0 items-center gap-1 text-left">
        <div class="min-w-0 flex-1">
            <p class="truncate text-sm font-bold tracking-tight">{{ $currentRestaurant?->name ?? 'KotBean' }}</p>
            <p class="text-xs text-slate-500 dark:text-slate-400">Switch business</p>
        </div>
        <svg class="h-4 w-4 shrink-0 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5"/></svg>
    </button>

    <div
        x-show="open"
        x-cloak
        x-transition
        class="absolute left-0 right-0 top-full z-50 mt-2 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-xl dark:border-slate-700 dark:bg-slate-900"
    >
        <div class="max-h-64 overflow-y-auto py-1">
            @foreach ($businesses as $business)
                <form method="POST" action="{{ route('businesses.switch') }}">
                    @csrf
                    <input type="hidden" name="restaurant_id" value="{{ $business->id }}">
                    <button
                        type="submit"
                        @class([
                            'flex w-full items-center justify-between gap-2 px-3 py-2 text-left text-sm hover:bg-slate-50 dark:hover:bg-slate-800',
                            'font-semibold text-amber-700 dark:text-amber-400' => $currentRestaurant?->id === $business->id,
                            'text-slate-700 dark:text-slate-200' => $currentRestaurant?->id !== $business->id,
                        ])
                    >
                        <span class="truncate">{{ $business->name }}</span>
                        @if ($currentRestaurant?->id === $business->id)
                            <span class="text-[10px] uppercase tracking-wide">Current</span>
                        @endif
                    </button>
                </form>
            @endforeach
        </div>

        <div class="border-t border-slate-200 p-2 dark:border-slate-700">
            <form method="POST" action="{{ route('businesses.store') }}" class="space-y-2">
                @csrf
                <input
                    type="text"
                    name="name"
                    required
                    maxlength="255"
                    placeholder="New business name"
                    class="w-full rounded-lg border-slate-300 text-xs focus:border-amber-500 focus:ring-amber-500 dark:border-slate-700 dark:bg-slate-800"
                >
                <button type="submit" class="w-full rounded-lg bg-amber-500 px-3 py-1.5 text-xs font-semibold text-white hover:bg-amber-600">
                    Create business
                </button>
            </form>
        </div>
    </div>
</div>
