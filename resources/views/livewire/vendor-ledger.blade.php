<div class="mx-auto max-w-7xl space-y-4 sm:space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-xl font-bold text-slate-900 sm:text-2xl">Vendor Ledger</h1>
            <p class="text-sm text-slate-500">Track vendor bills, payments, and outstanding balances.</p>
        </div>
        <a href="{{ route('vendors.index') }}" wire:navigate class="inline-flex items-center justify-center rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">Manage Vendors</a>
    </div>

    @if (session('success'))
        <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">{{ session('success') }}</div>
    @endif

    <div class="grid gap-4 sm:grid-cols-3">
        <x-metric-card label="Total Billed" :value="'₹' . number_format($summary['total_billed'], 2)" />
        <x-metric-card label="Total Paid" :value="'₹' . number_format($summary['total_paid'], 2)" />
        <x-metric-card label="Outstanding" :value="'₹' . number_format($summary['total_pending'], 2)" />
    </div>

    <div class="grid gap-3 rounded-xl border border-slate-200 bg-white p-4 shadow-sm sm:grid-cols-2 lg:grid-cols-4">
        <div>
            <label class="mb-1 block text-xs font-medium uppercase text-slate-500">Vendor</label>
            <select wire:model.live="vendorFilter" class="w-full rounded-lg border-slate-300 text-sm focus:border-amber-500 focus:ring-amber-500">
                <option value="">All vendors</option>
                @foreach ($vendors as $vendor)
                    <option value="{{ $vendor->id }}">{{ $vendor->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="mb-1 block text-xs font-medium uppercase text-slate-500">Status</label>
            <select wire:model.live="statusFilter" class="w-full rounded-lg border-slate-300 text-sm focus:border-amber-500 focus:ring-amber-500">
                <option value="all">All</option>
                @foreach ($statuses as $status)
                    <option value="{{ $status->value }}">{{ $status->label() }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="mb-1 block text-xs font-medium uppercase text-slate-500">From</label>
            <input wire:model.live="dateFrom" type="date" class="w-full rounded-lg border-slate-300 text-sm focus:border-amber-500 focus:ring-amber-500">
        </div>
        <div>
            <label class="mb-1 block text-xs font-medium uppercase text-slate-500">To</label>
            <input wire:model.live="dateTo" type="date" class="w-full rounded-lg border-slate-300 text-sm focus:border-amber-500 focus:ring-amber-500">
        </div>
    </div>

    @if ($entries->isNotEmpty())
        <div class="space-y-3 md:hidden">
            @foreach ($entries as $entry)
                <article wire:key="ledger-m-{{ $entry->id }}" class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="font-semibold text-slate-900">{{ $entry->title }}</p>
                            <p class="text-sm text-slate-500">{{ $entry->vendor?->name }} · {{ $entry->expense_date->format('M j, Y') }}</p>
                        </div>
                        <span @class([
                            'shrink-0 inline-flex rounded-full px-2 py-0.5 text-xs font-medium',
                            'bg-amber-50 text-amber-700' => $entry->payment_status === \App\Enums\ExpensePaymentStatus::Pending,
                            'bg-orange-50 text-orange-700' => $entry->payment_status === \App\Enums\ExpensePaymentStatus::Partial,
                            'bg-emerald-50 text-emerald-700' => $entry->payment_status === \App\Enums\ExpensePaymentStatus::Paid,
                        ])>{{ $entry->payment_status->label() }}</span>
                    </div>
                    <div class="mt-3 grid grid-cols-3 gap-2 text-sm">
                        <div><p class="text-xs text-slate-400">Amount</p><p class="font-semibold">₹{{ number_format((float) $entry->amount, 2) }}</p></div>
                        <div><p class="text-xs text-slate-400">Paid</p><p class="font-semibold text-emerald-600">₹{{ number_format((float) $entry->paid_amount, 2) }}</p></div>
                        <div><p class="text-xs text-slate-400">Due</p><p class="font-semibold text-amber-600">₹{{ number_format($entry->balance_due, 2) }}</p></div>
                    </div>
                    @if (auth()->user()->hasPermission('expenses.manage') && $entry->payment_status !== \App\Enums\ExpensePaymentStatus::Paid)
                        <div class="mt-3 flex gap-3 border-t border-slate-100 pt-3 text-sm">
                            <button wire:click="markPaid({{ $entry->id }})" type="button" class="font-medium text-emerald-600 hover:text-emerald-700">Mark paid</button>
                            <button wire:click="openPayment({{ $entry->id }})" type="button" class="font-medium text-amber-600 hover:text-amber-700">Record payment</button>
                        </div>
                    @endif
                </article>
            @endforeach
        </div>

        <div class="hidden overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm md:block">
            <x-table-scroll>
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase text-slate-500">Date</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase text-slate-500">Vendor</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase text-slate-500">Title</th>
                            <th class="px-4 py-3 text-right text-xs font-medium uppercase text-slate-500">Amount</th>
                            <th class="px-4 py-3 text-right text-xs font-medium uppercase text-slate-500">Paid</th>
                            <th class="px-4 py-3 text-right text-xs font-medium uppercase text-slate-500">Balance</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase text-slate-500">Status</th>
                            <th class="px-4 py-3 text-right text-xs font-medium uppercase text-slate-500">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($entries as $entry)
                            <tr wire:key="ledger-{{ $entry->id }}" class="hover:bg-slate-50">
                                <td class="px-4 py-3 text-sm text-slate-500">{{ $entry->expense_date->format('M j, Y') }}</td>
                                <td class="px-4 py-3 text-sm font-medium text-slate-900">{{ $entry->vendor?->name }}</td>
                                <td class="px-4 py-3 text-sm text-slate-600">{{ $entry->title }}</td>
                                <td class="px-4 py-3 text-right text-sm font-semibold text-slate-900">₹{{ number_format((float) $entry->amount, 2) }}</td>
                                <td class="px-4 py-3 text-right text-sm text-emerald-600">₹{{ number_format((float) $entry->paid_amount, 2) }}</td>
                                <td class="px-4 py-3 text-right text-sm font-semibold text-amber-600">₹{{ number_format($entry->balance_due, 2) }}</td>
                                <td class="px-4 py-3">
                                    <span @class([
                                        'inline-flex rounded-full px-2 py-0.5 text-xs font-medium',
                                        'bg-amber-50 text-amber-700' => $entry->payment_status === \App\Enums\ExpensePaymentStatus::Pending,
                                        'bg-orange-50 text-orange-700' => $entry->payment_status === \App\Enums\ExpensePaymentStatus::Partial,
                                        'bg-emerald-50 text-emerald-700' => $entry->payment_status === \App\Enums\ExpensePaymentStatus::Paid,
                                    ])>{{ $entry->payment_status->label() }}</span>
                                </td>
                                <td class="px-4 py-3 text-right text-sm">
                                    @if (auth()->user()->hasPermission('expenses.manage') && $entry->payment_status !== \App\Enums\ExpensePaymentStatus::Paid)
                                        <button wire:click="markPaid({{ $entry->id }})" type="button" class="font-medium text-emerald-600 hover:text-emerald-700">Mark paid</button>
                                        <button wire:click="openPayment({{ $entry->id }})" type="button" class="ml-3 font-medium text-amber-600 hover:text-amber-700">Pay</button>
                                    @else
                                        <span class="text-slate-400">—</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </x-table-scroll>
        </div>

        <div class="rounded-xl border border-slate-200 bg-white px-4 py-3 shadow-sm md:border-0 md:bg-transparent md:shadow-none md:px-0">{{ $entries->links() }}</div>
    @else
        <x-empty-state title="No vendor entries" description="Create an expense and assign a vendor to see ledger entries here." />
    @endif

    @if ($payingExpenseId)
        <div class="fixed inset-0 z-50 flex items-end justify-center bg-slate-900/50 p-4 sm:items-center">
            <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-2xl">
                <h3 class="text-lg font-bold text-slate-900">Record Payment</h3>
                <div class="mt-4">
                    <label class="mb-1 block text-sm font-medium text-slate-700">Amount (₹)</label>
                    <input wire:model="paymentAmount" type="number" step="0.01" min="0.01" class="w-full rounded-lg border-slate-300 text-sm focus:border-amber-500 focus:ring-amber-500">
                    @error('paymentAmount') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div class="mt-5 grid grid-cols-2 gap-2">
                    <button wire:click="cancelPayment" type="button" class="rounded-xl border border-slate-200 py-3 text-sm font-semibold text-slate-600">Cancel</button>
                    <button wire:click="recordPayment" wire:loading.attr="disabled" type="button" class="rounded-xl bg-amber-500 py-3 text-sm font-bold text-white hover:bg-amber-600 disabled:opacity-50">Save Payment</button>
                </div>
            </div>
        </div>
    @endif
</div>
