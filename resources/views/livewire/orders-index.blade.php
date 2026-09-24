<div class="mx-auto max-w-7xl space-y-4 sm:space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <h1 class="text-xl font-bold text-slate-900 sm:text-2xl">Orders</h1>
        <div class="grid grid-cols-2 gap-2 sm:flex sm:flex-wrap sm:items-center sm:gap-3">
            <select wire:model.live="statusFilter" class="col-span-2 rounded-lg border-slate-300 text-sm focus:border-amber-500 focus:ring-amber-500 sm:col-span-1">
                <option value="">All statuses</option>
                @foreach ($statuses as $status)
                    <option value="{{ $status->value }}">{{ $status->label() }}</option>
                @endforeach
            </select>
            <input wire:model.live="dateFrom" type="date" class="rounded-lg border-slate-300 text-sm focus:border-amber-500 focus:ring-amber-500">
            <input wire:model.live="dateTo" type="date" class="rounded-lg border-slate-300 text-sm focus:border-amber-500 focus:ring-amber-500">
        </div>
    </div>

    @if ($orders->isNotEmpty())
        {{-- Mobile cards --}}
        <div class="space-y-3 md:hidden">
            @foreach ($orders as $order)
                <article wire:key="order-m-{{ $order->id }}" class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="font-semibold text-slate-900">{{ $order->order_number }}</p>
                            <p class="mt-0.5 text-sm text-slate-500">{{ $order->created_at->format('M j, g:i A') }}</p>
                        </div>
                        <x-order-status-badge :status="$order->status" />
                    </div>
                    <div class="mt-3 grid grid-cols-2 gap-2 text-sm">
                        <div>
                            <p class="text-xs text-slate-400">Type</p>
                            <p class="font-medium text-slate-700">{{ $order->type->label() }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-slate-400">Table</p>
                            <p class="font-medium text-slate-700">{{ $order->table?->name ?? '—' }}</p>
                        </div>
                    </div>
                    <p class="mt-3 text-lg font-bold text-amber-600">₹{{ number_format((float) $order->total, 2) }}</p>
                </article>
            @endforeach
        </div>

        {{-- Desktop table --}}
        <div class="hidden overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm md:block">
            <x-table-scroll>
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase text-slate-500">Order</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase text-slate-500">Type</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase text-slate-500">Table</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase text-slate-500">Status</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase text-slate-500">Total</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase text-slate-500">Date</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($orders as $order)
                            <tr wire:key="order-{{ $order->id }}" class="hover:bg-slate-50">
                                <td class="px-4 py-3 text-sm font-semibold text-slate-900">{{ $order->order_number }}</td>
                                <td class="px-4 py-3 text-sm text-slate-600">{{ $order->type->label() }}</td>
                                <td class="px-4 py-3 text-sm text-slate-600">{{ $order->table?->name ?? '—' }}</td>
                                <td class="px-4 py-3"><x-order-status-badge :status="$order->status" /></td>
                                <td class="px-4 py-3 text-sm font-semibold text-amber-600">₹{{ number_format((float) $order->total, 2) }}</td>
                                <td class="px-4 py-3 text-sm text-slate-500">{{ $order->created_at->format('M j, g:i A') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </x-table-scroll>
        </div>

        <div class="rounded-xl border border-slate-200 bg-white px-4 py-3 shadow-sm md:border-0 md:bg-transparent md:shadow-none md:px-0">{{ $orders->links() }}</div>
    @else
        <div class="rounded-xl border border-slate-200 bg-white p-8 shadow-sm">
            <x-empty-state title="No orders found" description="Adjust your filters or create a new order from POS." />
        </div>
    @endif
</div>
