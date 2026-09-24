<div class="mx-auto max-w-7xl space-y-4 sm:space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <h1 class="text-xl font-bold text-slate-900 sm:text-2xl">Customers</h1>
        <input wire:model.live.debounce.300ms="search" type="search" placeholder="Search by name, phone, email..." class="w-full rounded-lg border-slate-300 text-sm focus:border-amber-500 focus:ring-amber-500 sm:w-80">
    </div>

    <div class="flex gap-1 rounded-xl border border-slate-200 bg-slate-50 p-1">
        <button
            wire:click="$set('tab', 'list')"
            type="button"
            @class([
                'flex-1 rounded-lg px-4 py-2.5 text-sm font-semibold transition',
                'bg-white text-slate-900 shadow-sm' => $tab === 'list',
                'text-slate-600 hover:text-slate-900' => $tab !== 'list',
            ])
        >
            All Customers
        </button>
        <button
            wire:click="$set('tab', 'whatsapp')"
            type="button"
            @class([
                'flex-1 rounded-lg px-4 py-2.5 text-sm font-semibold transition',
                'bg-white text-slate-900 shadow-sm' => $tab === 'whatsapp',
                'text-slate-600 hover:text-slate-900' => $tab !== 'whatsapp',
            ])
        >
            WhatsApp
        </button>
    </div>

    @if (session('success'))
        <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">{{ session('success') }}</div>
    @endif

    @if (session('error'))
        <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{{ session('error') }}</div>
    @endif

    @if ($tab === 'whatsapp')
        @if (! $twilioConfigured)
            <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                Twilio WhatsApp is not configured. Add <code class="rounded bg-amber-100 px-1">TWILIO_SID</code>, <code class="rounded bg-amber-100 px-1">TWILIO_AUTH_TOKEN</code>, <code class="rounded bg-amber-100 px-1">TWILIO_WHATSAPP_FROM</code>, and <code class="rounded bg-amber-100 px-1">TWILIO_WHATSAPP_CONTENT_SID</code> to your environment.
            </div>
        @endif

        @if ($customers->isEmpty())
            <x-empty-state title="No customers to message" description="Customers with a phone number and at least one order will appear here." />
        @else
            <div class="grid gap-4 lg:grid-cols-5">
                <div class="space-y-3 lg:col-span-2">
                    @foreach ($customers as $customer)
                        <button
                            wire:key="wa-customer-{{ $customer->id }}"
                            wire:click="selectCustomer({{ $customer->id }})"
                            type="button"
                            @class([
                                'w-full rounded-xl border p-4 text-left transition',
                                'border-amber-300 bg-amber-50 ring-1 ring-amber-200' => $selectedCustomerId === $customer->id,
                                'border-slate-200 bg-white hover:border-slate-300' => $selectedCustomerId !== $customer->id,
                            ])
                        >
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="font-semibold text-slate-900">{{ $customer->name }}</p>
                                    <p class="text-sm text-slate-500">{{ $customer->phone }}</p>
                                </div>
                                <p class="shrink-0 text-sm font-semibold text-amber-600">₹{{ number_format((float) ($customer->latestOrder?->total ?? 0), 0) }}</p>
                            </div>
                            <div class="mt-2 text-xs text-slate-500">
                                Last order: {{ $customer->latestOrder?->order_number ?? '—' }}
                                · {{ $customer->last_order_at?->format('M j, Y') ?? '—' }}
                            </div>
                        </button>
                    @endforeach
                </div>

                <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm lg:col-span-3">
                    @if ($selectedCustomer && $messagePreview)
                        <div class="space-y-4">
                            <div>
                                <h2 class="text-lg font-bold text-slate-900">Message Preview</h2>
                                <p class="text-sm text-slate-500">To: {{ $selectedCustomer->name }} ({{ $selectedCustomer->phone }})</p>
                            </div>

                            <pre class="max-h-64 overflow-auto whitespace-pre-wrap rounded-lg border border-slate-200 bg-slate-50 p-4 text-sm text-slate-700">{{ $messagePreview }}</pre>

                            <div>
                                <label for="extraNote" class="mb-1 block text-sm font-medium text-slate-700">Optional note (appended to message)</label>
                                <textarea
                                    wire:model.live.debounce.500ms="extraNote"
                                    id="extraNote"
                                    rows="2"
                                    placeholder="e.g. Show this message for 10% off your next visit!"
                                    class="w-full rounded-lg border-slate-300 text-sm focus:border-amber-500 focus:ring-amber-500"
                                ></textarea>
                            </div>

                            @if ($canSendMessages)
                                <button
                                    wire:click="sendMessage({{ $selectedCustomer->id }})"
                                    wire:loading.attr="disabled"
                                    wire:confirm="Send WhatsApp message to {{ $selectedCustomer->name }}?"
                                    type="button"
                                    @class([
                                        'w-full rounded-lg px-4 py-3 text-sm font-bold text-white transition',
                                        'bg-emerald-600 hover:bg-emerald-700 disabled:opacity-50' => $twilioConfigured,
                                        'cursor-not-allowed bg-slate-300' => ! $twilioConfigured,
                                    ])
                                    @disabled(! $twilioConfigured)
                                >
                                    <span wire:loading.remove wire:target="sendMessage">Send WhatsApp Message</span>
                                    <span wire:loading wire:target="sendMessage">Sending…</span>
                                </button>
                            @else
                                <p class="rounded-lg border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-600">
                                    You need customer management permission to send WhatsApp messages.
                                </p>
                            @endif
                        </div>
                    @else
                        <div class="flex h-full min-h-48 flex-col items-center justify-center text-center">
                            <p class="text-sm font-medium text-slate-700">Select a customer</p>
                            <p class="mt-1 text-sm text-slate-500">Choose a customer on the left to preview their last order message.</p>
                        </div>
                    @endif
                </div>
            </div>
        @endif
    @elseif ($customers->isNotEmpty())
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
                    <div class="mt-3 flex flex-wrap justify-between gap-2 border-t border-slate-100 pt-3 text-sm text-slate-600">
                        <span>{{ $customer->total_orders }} orders</span>
                        @if ($loyaltyEnabled)
                            <span class="font-semibold text-violet-600">{{ number_format($customer->loyalty_points) }} pts</span>
                        @endif
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
                            @if ($loyaltyEnabled)
                                <th class="px-4 py-3 text-right text-xs font-medium uppercase text-slate-500">Points</th>
                            @endif
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
                                @if ($loyaltyEnabled)
                                    <td class="px-4 py-3 text-right text-sm font-semibold text-violet-600">{{ number_format($customer->loyalty_points) }}</td>
                                @endif
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
