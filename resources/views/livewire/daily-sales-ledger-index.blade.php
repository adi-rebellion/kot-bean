<div class="mx-auto max-w-4xl space-y-6">
    <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <h1 class="text-xl font-bold text-slate-900 sm:text-2xl">Daily Sales Ledger</h1>
            <p class="mt-1 text-sm text-slate-500">Record end-of-day cash and online sales when POS orders were missed. You can edit any past day.</p>
        </div>
        <a href="{{ route('cash-register.index') }}" wire:navigate class="text-sm font-medium text-amber-600 hover:text-amber-700">Cash register →</a>
    </div>

    @if (session('success'))
        <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{{ session('error') }}</div>
    @endif

  @if ($canManage)
        <form wire:submit="save" class="space-y-4 rounded-xl border border-slate-200 bg-white p-4 shadow-sm sm:p-6">
            <h2 class="text-lg font-semibold text-slate-900">{{ $editingId ? 'Update day' : 'Log a day' }}</h2>
            <div class="grid gap-4 sm:grid-cols-3">
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700">Date</label>
                    <input wire:model.live="saleDate" type="date" max="{{ today()->toDateString() }}" class="w-full rounded-lg border-slate-300 text-sm focus:border-amber-500 focus:ring-amber-500">
                    @error('saleDate') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700">Cash (₹)</label>
                    <input wire:model="cashAmount" type="number" step="0.01" min="0" placeholder="0" class="w-full rounded-lg border-slate-300 text-sm focus:border-amber-500 focus:ring-amber-500">
                    @error('cashAmount') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700">Online (₹)</label>
                    <p class="mb-1 text-xs text-slate-400">UPI, card &amp; other</p>
                    <input wire:model="onlineAmount" type="number" step="0.01" min="0" placeholder="0" class="w-full rounded-lg border-slate-300 text-sm focus:border-amber-500 focus:ring-amber-500">
                    @error('onlineAmount') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium text-slate-700">Notes (optional)</label>
                <textarea wire:model="notes" rows="2" placeholder="e.g. Zomato payout missing in POS" class="w-full rounded-lg border-slate-300 text-sm focus:border-amber-500 focus:ring-amber-500"></textarea>
            </div>

            <div class="rounded-lg border border-slate-200 bg-slate-50 p-3 text-sm text-slate-600">
                <p class="font-medium text-slate-800">POS recorded for this date (reference)</p>
                <p class="mt-1">Cash: ₹{{ number_format($posTotals['cash'], 2) }} · Online: ₹{{ number_format($posTotals['online'], 2) }} · Total: ₹{{ number_format($posTotals['total'], 2) }}</p>
                @if ($entryForSelectedDate)
                    <p class="mt-2 text-xs text-amber-700">Ledger already has an entry for this date — saving will update it.</p>
                @endif
            </div>

            <div class="flex gap-2">
                <button type="submit" class="rounded-lg bg-amber-500 px-5 py-2.5 text-sm font-bold text-white hover:bg-amber-600">
                    {{ $editingId ? 'Update entry' : 'Save entry' }}
                </button>
                @if ($editingId)
                    <button wire:click="cancelEdit" type="button" class="rounded-lg border border-slate-300 px-5 py-2.5 text-sm font-semibold text-slate-700">Cancel</button>
                @endif
            </div>
        </form>
    @else
        <p class="rounded-lg border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-600">You can view the ledger. Ask someone with payment access to add or edit entries.</p>
    @endif

    <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-100 px-4 py-3">
            <h2 class="text-base font-semibold text-slate-900">Ledger history</h2>
        </div>
        @if ($entries->isNotEmpty())
            <x-table-scroll>
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50 text-xs uppercase text-slate-500">
                        <tr>
                            <th class="px-4 py-3 text-left">Date</th>
                            <th class="px-4 py-3 text-right">Cash</th>
                            <th class="px-4 py-3 text-right">Online</th>
                            <th class="px-4 py-3 text-right">Total</th>
                            <th class="px-4 py-3 text-left">Notes</th>
                            @if ($canManage)
                                <th class="px-4 py-3 text-right">Actions</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($entries as $entry)
                            <tr wire:key="ledger-{{ $entry->id }}" class="hover:bg-slate-50">
                                <td class="px-4 py-3 font-medium text-slate-900">{{ $entry->sale_date->format('D, M j, Y') }}</td>
                                <td class="px-4 py-3 text-right text-slate-700">₹{{ number_format((float) $entry->cash_amount, 2) }}</td>
                                <td class="px-4 py-3 text-right text-slate-700">₹{{ number_format((float) $entry->online_amount, 2) }}</td>
                                <td class="px-4 py-3 text-right font-semibold text-slate-900">₹{{ number_format($entry->totalAmount(), 2) }}</td>
                                <td class="max-w-[12rem] truncate px-4 py-3 text-slate-500" title="{{ $entry->notes }}">{{ $entry->notes ?? '—' }}</td>
                                @if ($canManage)
                                    <td class="px-4 py-3 text-right">
                                        <button wire:click="edit({{ $entry->id }})" type="button" class="text-amber-600 hover:text-amber-700">Edit</button>
                                        <button wire:click="delete({{ $entry->id }})" wire:confirm="Remove this ledger entry?" type="button" class="ml-2 text-red-600 hover:text-red-700">Delete</button>
                                    </td>
                                @endif
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </x-table-scroll>
        @else
            <div class="p-8">
                <x-empty-state title="No entries yet" description="Log today’s cash and online totals when you close the shop." />
            </div>
        @endif
    </div>
</div>