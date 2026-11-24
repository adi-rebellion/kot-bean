<div class="mx-auto max-w-7xl space-y-4 sm:space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-xl font-bold text-slate-900 sm:text-2xl">Staff Expense Ledger</h1>
            <p class="text-sm text-slate-500">Who paid expenses and what is still pending on each team member.</p>
        </div>
        <a href="{{ route('expenses.index') }}" wire:navigate class="inline-flex items-center justify-center rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">All Expenses</a>
    </div>

    <div class="grid gap-4 sm:grid-cols-2">
        <x-metric-card label="Paid by staff (total)" :value="'₹' . number_format($summary['total_paid_by'], 2)" />
        <x-metric-card label="Pending on staff" :value="'₹' . number_format($summary['total_pending_on'], 2)" />
    </div>

    <div class="grid gap-3 rounded-xl border border-slate-200 bg-white p-4 shadow-sm sm:grid-cols-2 lg:grid-cols-4">
        <div>
            <label class="mb-1 block text-xs font-medium uppercase text-slate-500">Staff</label>
            <select wire:model.live="staffFilter" class="w-full rounded-lg border-slate-300 text-sm focus:border-amber-500 focus:ring-amber-500">
                <option value="">All staff</option>
                @foreach ($staffMembers as $member)
                    <option value="{{ $member->id }}">{{ $member->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="mb-1 block text-xs font-medium uppercase text-slate-500">Show</label>
            <select wire:model.live="involvementFilter" class="w-full rounded-lg border-slate-300 text-sm focus:border-amber-500 focus:ring-amber-500">
                <option value="all">Paid by or pending on</option>
                <option value="paid_by">Paid by only</option>
                <option value="pending_on">Pending on only</option>
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
        <div class="hidden overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm md:block">
            <x-table-scroll>
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase text-slate-500">Date</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase text-slate-500">Title</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase text-slate-500">Paid by</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase text-slate-500">Pending on</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase text-slate-500">Vendor</th>
                            <th class="px-4 py-3 text-right text-xs font-medium uppercase text-slate-500">Amount</th>
                            <th class="px-4 py-3 text-right text-xs font-medium uppercase text-slate-500">Due</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($entries as $entry)
                            <tr wire:key="staff-ledger-{{ $entry->id }}" class="hover:bg-slate-50">
                                <td class="px-4 py-3 text-sm text-slate-500">{{ $entry->expense_date->format('M j, Y') }}</td>
                                <td class="px-4 py-3 text-sm font-medium text-slate-900">{{ $entry->title }}</td>
                                <td class="px-4 py-3 text-sm text-slate-600">{{ $entry->paidBy?->name ?? '—' }}</td>
                                <td class="px-4 py-3 text-sm text-slate-600">{{ $entry->pendingOn?->name ?? '—' }}</td>
                                <td class="px-4 py-3 text-sm text-slate-600">{{ $entry->vendor?->name ?? '—' }}</td>
                                <td class="px-4 py-3 text-right text-sm font-semibold text-amber-600">₹{{ number_format((float) $entry->amount, 2) }}</td>
                                <td class="px-4 py-3 text-right text-sm font-semibold text-slate-700">₹{{ number_format($entry->balance_due, 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </x-table-scroll>
        </div>

        <div class="mt-4">{{ $entries->links() }}</div>
    @else
        <x-empty-state title="No staff-linked expenses" description="Add paid by or pending on when recording expenses to build this ledger.">
            <a href="{{ route('expenses.index') }}" wire:navigate class="mt-2 inline-block rounded-lg bg-amber-500 px-4 py-2 text-sm font-semibold text-white hover:bg-amber-600">Add expense</a>
        </x-empty-state>
    @endif
</div>