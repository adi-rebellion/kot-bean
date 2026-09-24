<div class="mx-auto max-w-7xl space-y-4 sm:space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <h1 class="text-xl font-bold text-slate-900 sm:text-2xl">Customers</h1>
        <input wire:model.live.debounce.300ms="search" type="search" placeholder="Search by name, phone, email..." class="w-full rounded-lg border-slate-300 text-sm focus:border-amber-500 focus:ring-amber-500 sm:w-80">
    </div>

    @if ($customers->isNotEmpty())
        {{-- Mobile cards --}}
        <div class="space-y-3 md:hidden">
            @foreach ($customers as $customer)
                <article wire:key="customer-m-{{ $customer->id }}" class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="font-semibold text-slate-900">{{ $customer->name }}</p>
                            <p class="text-sm text-slate-500">{{ $customer->phone ?? 'No phone' }}</p>
                            @if ($customer->email)
                                <p class="truncate text-sm text-slate-500">{{ $customer->email }}</p>
                            @endif
                        </div>
                        <p class="shrink-0 text-lg font-bold text-amber-600">₹{{ number_format((float) $customer->total_spent, 0) }}</p>
                    </div>
                    <div class="mt-3 flex justify-between border-t border-slate-100 pt-3 text-sm text-slate-600">
                        <span>{{ $customer->total_orders }} orders</span>
                        <span>Last: {{ $customer->last_order_at?->format('M j, Y') ?? '—' }}</span>
                    </div>
                </article>
            @endforeach
        </div>

        {{-- Desktop table --}}
        <div class="hidden overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm md:block">
            <x-table-scroll>
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase text-slate-500">Name</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase text-slate-500">Phone</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase text-slate-500">Email</th>
                            <th class="px-4 py-3 text-right text-xs font-medium uppercase text-slate-500">Orders</th>
                            <th class="px-4 py-3 text-right text-xs font-medium uppercase text-slate-500">Total Spent</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase text-slate-500">Last Order</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($customers as $customer)
                            <tr wire:key="customer-{{ $customer->id }}" class="hover:bg-slate-50">
                                <td class="px-4 py-3 text-sm font-semibold text-slate-900">{{ $customer->name }}</td>
                                <td class="px-4 py-3 text-sm text-slate-600">{{ $customer->phone ?? '—' }}</td>
                                <td class="px-4 py-3 text-sm text-slate-600">{{ $customer->email ?? '—' }}</td>
                                <td class="px-4 py-3 text-right text-sm text-slate-600">{{ $customer->total_orders }}</td>
                                <td class="px-4 py-3 text-right text-sm font-semibold text-amber-600">₹{{ number_format((float) $customer->total_spent, 2) }}</td>
                                <td class="px-4 py-3 text-sm text-slate-500">{{ $customer->last_order_at?->format('M j, Y') ?? '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </x-table-scroll>
        </div>
    @else
        <x-empty-state title="No customers found" description="Customer records will appear as orders are placed." />
    @endif
</div>
