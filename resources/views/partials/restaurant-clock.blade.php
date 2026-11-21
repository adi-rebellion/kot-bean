@if (isset($restaurantLocalTime, $restaurantTimezoneLabel))
    <div
        class="hidden items-center gap-1.5 rounded-lg border border-slate-200 bg-slate-50 px-2.5 py-1 text-xs text-slate-600 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300 sm:flex"
        title="{{ $restaurantTimezoneLabel }}"
    >
        <svg class="h-3.5 w-3.5 shrink-0 opacity-60" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        <span class="font-semibold tabular-nums">{{ $restaurantLocalTime }}</span>
        <span class="text-slate-400">{{ $restaurantTimezoneAbbrev ?? '' }}</span>
    </div>
@endif