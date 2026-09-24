<div wire:poll.5s="refreshKots" class="flex min-h-screen flex-col">
    <header class="shrink-0 border-b border-slate-800 bg-slate-950">
        <div class="mx-auto flex max-w-[1600px] flex-wrap items-center justify-between gap-4 px-4 py-4 sm:px-6">
            <div class="flex items-center gap-3">
                <a href="{{ route('dashboard') }}" class="flex h-10 w-10 items-center justify-center rounded-xl border border-slate-700 text-slate-400 hover:bg-slate-900 hover:text-white">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                </a>
                <div>
                    <h1 class="text-lg font-bold text-white sm:text-xl">Kitchen Display</h1>
                    <p class="text-xs text-slate-400 sm:text-sm">{{ auth()->user()->restaurant?->name }} · {{ now()->format('g:i A') }}</p>
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <span class="rounded-lg bg-amber-500/15 px-3 py-1.5 text-sm font-semibold text-amber-300">New {{ $newTickets->count() }}</span>
                <span class="rounded-lg bg-orange-500/15 px-3 py-1.5 text-sm font-semibold text-orange-300">Cooking {{ $cookingTickets->count() }}</span>
                <span class="rounded-lg bg-emerald-500/15 px-3 py-1.5 text-sm font-semibold text-emerald-300">Ready {{ $readyTickets->count() }}</span>
                <button
                    wire:click="refreshKots"
                    wire:loading.attr="disabled"
                    wire:target="refreshKots"
                    type="button"
                    class="rounded-lg border border-slate-700 bg-slate-900 px-4 py-1.5 text-sm font-semibold text-white hover:bg-slate-800 disabled:opacity-50"
                >
                    <span wire:loading.remove wire:target="refreshKots">Refresh</span>
                    <span wire:loading wire:target="refreshKots">Syncing…</span>
                </button>
            </div>
        </div>
    </header>

    @if ($kots->isEmpty())
        <div class="flex flex-1 items-center justify-center p-8">
            <div class="max-w-md rounded-2xl border border-slate-800 bg-slate-900 px-8 py-10 text-center">
                <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-2xl bg-slate-800 text-3xl">👨‍🍳</div>
                <h2 class="text-xl font-bold text-white">Kitchen is clear</h2>
                <p class="mt-2 text-sm text-slate-400">Orders sent from POS will appear here. This page auto-refreshes every 5 seconds.</p>
            </div>
        </div>
    @else
        <main class="mx-auto w-full max-w-[1600px] flex-1 p-4 sm:p-6">
            <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                {{-- New --}}
                <section class="flex flex-col rounded-2xl border border-slate-800 bg-slate-900/50">
                    <div class="flex items-center justify-between border-b border-slate-800 px-4 py-3">
                        <h2 class="text-sm font-bold uppercase tracking-wide text-amber-300">New Orders</h2>
                        <span class="rounded-full bg-amber-500/20 px-2 py-0.5 text-xs font-bold text-amber-300">{{ $newTickets->count() }}</span>
                    </div>
                    <div class="space-y-3 p-3">
                        @forelse ($newTickets as $kot)
                            <x-kitchen-ticket :kot="$kot" action-label="Start Cooking" action-class="bg-amber-500 hover:bg-amber-400" />
                        @empty
                            <p class="py-8 text-center text-sm text-slate-500">No new tickets</p>
                        @endforelse
                    </div>
                </section>

                {{-- Cooking --}}
                <section class="flex flex-col rounded-2xl border border-slate-800 bg-slate-900/50">
                    <div class="flex items-center justify-between border-b border-slate-800 px-4 py-3">
                        <h2 class="text-sm font-bold uppercase tracking-wide text-orange-300">Cooking</h2>
                        <span class="rounded-full bg-orange-500/20 px-2 py-0.5 text-xs font-bold text-orange-300">{{ $cookingTickets->count() }}</span>
                    </div>
                    <div class="space-y-3 p-3">
                        @forelse ($cookingTickets as $kot)
                            <x-kitchen-ticket :kot="$kot" action-label="Mark Ready" action-class="bg-orange-500 hover:bg-orange-400" />
                        @empty
                            <p class="py-8 text-center text-sm text-slate-500">Nothing cooking</p>
                        @endforelse
                    </div>
                </section>

                {{-- Ready --}}
                <section class="flex flex-col rounded-2xl border border-emerald-500/30 bg-emerald-950/30">
                    <div class="flex items-center justify-between border-b border-emerald-500/20 px-4 py-3">
                        <h2 class="text-sm font-bold uppercase tracking-wide text-emerald-300">Ready to Serve</h2>
                        <span class="rounded-full bg-emerald-500/20 px-2 py-0.5 text-xs font-bold text-emerald-300">{{ $readyTickets->count() }}</span>
                    </div>
                    <div class="space-y-3 p-3">
                        @forelse ($readyTickets as $kot)
                            <x-kitchen-ticket :kot="$kot" action-label="Mark Served" action-class="bg-emerald-500 hover:bg-emerald-400" />
                        @empty
                            <p class="py-8 text-center text-sm text-slate-500">Nothing ready yet</p>
                        @endforelse
                    </div>
                </section>
            </div>
        </main>
    @endif
</div>
