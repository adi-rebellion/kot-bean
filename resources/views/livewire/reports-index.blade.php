<div class="mx-auto max-w-7xl space-y-4 sm:space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <h1 class="text-xl font-bold text-slate-900 sm:text-2xl">Sales Reports</h1>
        <div class="grid grid-cols-2 gap-2 sm:flex sm:items-center sm:gap-3">
            <input wire:model.live="dateFrom" type="date" class="rounded-lg border-slate-300 text-sm focus:border-amber-500 focus:ring-amber-500">
            <input wire:model.live="dateTo" type="date" class="rounded-lg border-slate-300 text-sm focus:border-amber-500 focus:ring-amber-500">
            <a
                href="{{ route('reports.download', ['date_from' => $dateFrom, 'date_to' => $dateTo]) }}"
                class="col-span-2 inline-flex items-center justify-center gap-2 rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50 sm:col-span-1"
            >
                <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" />
                </svg>
                Download CSV
            </a>
        </div>
    </div>

    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <x-metric-card label="Orders" :value="number_format($salesReport['order_count'] ?? 0)" />
        <x-metric-card label="Gross Sales" :value="'₹' . number_format($salesReport['gross_sales'] ?? 0, 2)" />
        <x-metric-card label="Tax Collected" :value="'₹' . number_format($salesReport['tax_collected'] ?? 0, 2)" />
        <x-metric-card label="Net Sales" :value="'₹' . number_format($salesReport['net_sales'] ?? 0, 2)" />
    </div>

    <div class="grid gap-6 lg:grid-cols-2">
        <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-100 px-4 py-3">
                <h2 class="text-lg font-semibold text-slate-900">Product Performance</h2>
            </div>
            @if ($productReport->isNotEmpty())
                <x-table-scroll>
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-4 py-2 text-left text-xs font-medium uppercase text-slate-500">Product</th>
                            <th class="px-4 py-2 text-right text-xs font-medium uppercase text-slate-500">Units</th>
                            <th class="px-4 py-2 text-right text-xs font-medium uppercase text-slate-500">Revenue</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($productReport as $row)
                            <tr wire:key="prod-rpt-{{ $row->product_id }}">
                                <td class="px-4 py-3 text-sm font-medium text-slate-900">{{ $row->product_name }}</td>
                                <td class="px-4 py-3 text-right text-sm text-slate-600">{{ $row->units_sold }}</td>
                                <td class="px-4 py-3 text-right text-sm font-semibold text-amber-600">₹{{ number_format((float) $row->revenue, 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                </x-table-scroll>
            @else
                <div class="p-6"><x-empty-state title="No product data" /></div>
            @endif
        </div>

        <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-100 px-4 py-3">
                <h2 class="text-lg font-semibold text-slate-900">Payment Methods</h2>
            </div>
            @if ($paymentReport->isNotEmpty())
                <x-table-scroll>
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-4 py-2 text-left text-xs font-medium uppercase text-slate-500">Method</th>
                            <th class="px-4 py-2 text-right text-xs font-medium uppercase text-slate-500">Count</th>
                            <th class="px-4 py-2 text-right text-xs font-medium uppercase text-slate-500">Total</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($paymentReport as $row)
                            <tr wire:key="pay-rpt-{{ $row->method }}">
                                <td class="px-4 py-3 text-sm font-medium text-slate-900">{{ ($row->method instanceof \App\Enums\PaymentMethod ? $row->method : \App\Enums\PaymentMethod::from($row->method))->label() }}</td>
                                <td class="px-4 py-3 text-right text-sm text-slate-600">{{ $row->transaction_count }}</td>
                                <td class="px-4 py-3 text-right text-sm font-semibold text-amber-600">₹{{ number_format((float) $row->total_amount, 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                </x-table-scroll>
            @else
                <div class="p-6"><x-empty-state title="No payment data" /></div>
            @endif
        </div>
    </div>
</div>
