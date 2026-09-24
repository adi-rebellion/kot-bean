<div class="mx-auto max-w-7xl space-y-4 sm:space-y-6">
    <div>
        <h1 class="text-xl font-bold text-slate-900 sm:text-2xl">Cash Register</h1>
        <p class="text-sm text-slate-500">Open the register in the morning and close it at end of day.</p>
    </div>

    @if (session('success'))
        <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{{ session('error') }}</div>
    @endif

    <div class="grid gap-4 lg:grid-cols-2">
        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm sm:p-6">
            <h2 class="text-lg font-semibold text-slate-900">Today's Session</h2>
            <p class="mt-1 text-sm text-slate-500">{{ now()->format('l, M j, Y') }}</p>

            @if ($todaySession?->isOpen())
                <div class="mt-4 space-y-2 rounded-lg bg-emerald-50 p-4 text-sm text-emerald-800">
                    <p><span class="font-semibold">Status:</span> Open since {{ $todaySession->opened_at->format('g:i A') }}</p>
                    <p><span class="font-semibold">Opening float:</span> ₹{{ number_format((float) $todaySession->opening_float, 2) }}</p>
                    <p><span class="font-semibold">Cash sales today:</span> ₹{{ number_format($paymentSummary['cash'], 2) }}</p>
                    <p><span class="font-semibold">Expected in drawer:</span> ₹{{ number_format((float) $expectedCash, 2) }}</p>
                </div>

                <form wire:submit="closeRegister" class="mt-4 space-y-3">
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">Actual cash counted (₹)</label>
                        <input wire:model="actualCash" type="number" step="0.01" min="0" class="w-full rounded-lg border-slate-300 text-sm focus:border-amber-500 focus:ring-amber-500">
                        @error('actualCash') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">Notes</label>
                        <textarea wire:model="closeNotes" rows="2" class="w-full rounded-lg border-slate-300 text-sm focus:border-amber-500 focus:ring-amber-500" placeholder="Optional closing notes"></textarea>
                    </div>
                    <button type="submit" class="w-full rounded-lg bg-slate-900 px-4 py-3 text-sm font-bold text-white hover:bg-slate-800">Close Register</button>
                </form>
            @elseif ($todaySession?->closed_at)
                <div class="mt-4 space-y-2 rounded-lg bg-slate-50 p-4 text-sm text-slate-700">
                    <p><span class="font-semibold">Closed at:</span> {{ $todaySession->closed_at->format('g:i A') }}</p>
                    <p><span class="font-semibold">Expected cash:</span> ₹{{ number_format((float) $todaySession->expected_cash, 2) }}</p>
                    <p><span class="font-semibold">Actual cash:</span> ₹{{ number_format((float) $todaySession->actual_cash, 2) }}</p>
                    <p @class(['font-semibold', 'text-emerald-600' => (float) $todaySession->cash_difference === 0.0, 'text-red-600' => (float) $todaySession->cash_difference !== 0.0])>
                        Difference: ₹{{ number_format((float) $todaySession->cash_difference, 2) }}
                    </p>
                    <p><span class="font-semibold">Total sales:</span> ₹{{ number_format((float) $todaySession->total_sales, 2) }}</p>
                </div>
            @else
                <form wire:submit="openRegister" class="mt-4 space-y-3">
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">Opening float (₹)</label>
                        <input wire:model="openingFloat" type="number" step="0.01" min="0" placeholder="Cash in drawer at start" class="w-full rounded-lg border-slate-300 text-sm focus:border-amber-500 focus:ring-amber-500">
                        @error('openingFloat') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <button type="submit" class="w-full rounded-lg bg-amber-500 px-4 py-3 text-sm font-bold text-white hover:bg-amber-600">Open Register</button>
                </form>
            @endif
        </div>

        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm sm:p-6">
            <h2 class="text-lg font-semibold text-slate-900">Today's Payments</h2>
            <div class="mt-4 space-y-2 text-sm">
                <div class="flex justify-between"><span class="text-slate-500">Cash</span><span class="font-semibold">₹{{ number_format($paymentSummary['cash'], 2) }}</span></div>
                <div class="flex justify-between"><span class="text-slate-500">UPI</span><span class="font-semibold">₹{{ number_format($paymentSummary['upi'], 2) }}</span></div>
                <div class="flex justify-between"><span class="text-slate-500">Card</span><span class="font-semibold">₹{{ number_format($paymentSummary['card'], 2) }}</span></div>
                <div class="flex justify-between"><span class="text-slate-500">Other</span><span class="font-semibold">₹{{ number_format($paymentSummary['other'], 2) }}</span></div>
                <div class="flex justify-between border-t border-slate-200 pt-2 text-base font-bold"><span>Total</span><span class="text-amber-600">₹{{ number_format($paymentSummary['total'], 2) }}</span></div>
            </div>
        </div>
    </div>

    @if ($recentSessions->isNotEmpty())
        <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-100 px-4 py-3">
                <h2 class="text-base font-semibold text-slate-900">Recent Closes</h2>
            </div>
            <x-table-scroll>
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase text-slate-500">Date</th>
                            <th class="px-4 py-3 text-right text-xs font-medium uppercase text-slate-500">Expected</th>
                            <th class="px-4 py-3 text-right text-xs font-medium uppercase text-slate-500">Actual</th>
                            <th class="px-4 py-3 text-right text-xs font-medium uppercase text-slate-500">Diff</th>
                            <th class="px-4 py-3 text-right text-xs font-medium uppercase text-slate-500">Sales</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($recentSessions as $session)
                            <tr wire:key="reg-{{ $session->id }}">
                                <td class="px-4 py-3 text-sm text-slate-700">{{ $session->session_date->format('M j, Y') }}</td>
                                <td class="px-4 py-3 text-right text-sm">₹{{ number_format((float) $session->expected_cash, 2) }}</td>
                                <td class="px-4 py-3 text-right text-sm">₹{{ number_format((float) $session->actual_cash, 2) }}</td>
                                <td class="px-4 py-3 text-right text-sm font-semibold {{ (float) $session->cash_difference === 0.0 ? 'text-emerald-600' : 'text-red-600' }}">₹{{ number_format((float) $session->cash_difference, 2) }}</td>
                                <td class="px-4 py-3 text-right text-sm font-semibold text-amber-600">₹{{ number_format((float) $session->total_sales, 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </x-table-scroll>
        </div>
    @endif
</div>
