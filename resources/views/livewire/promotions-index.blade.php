<div class="mx-auto max-w-7xl space-y-4 sm:space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-xl font-bold text-slate-900 sm:text-2xl">Discounts & Promotions</h1>
            <p class="text-sm text-slate-500">Create discounts with rules, expiry, and auto-apply for POS.</p>
        </div>
        @if (auth()->user()->hasPermission('promotions.manage'))
            <button wire:click="create" type="button" class="rounded-lg bg-amber-500 px-4 py-2.5 text-sm font-semibold text-white hover:bg-amber-600">Create Discount</button>
        @endif
    </div>

    @if (session('success'))
        <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">{{ session('success') }}</div>
    @endif

    @if (session('error'))
        <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{{ session('error') }}</div>
    @endif

    @if ($showForm)
        <form wire:submit="save" class="space-y-4 rounded-xl border border-slate-200 bg-white p-4 shadow-sm sm:p-6">
            <h2 class="text-lg font-semibold text-slate-900">{{ $editingId ? 'Edit Promotion' : 'New Promotion' }}</h2>

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700">Name</label>
                    <input wire:model="name" type="text" placeholder="Morning 10% off" class="w-full rounded-lg border-slate-300 text-sm focus:border-amber-500 focus:ring-amber-500">
                    @error('name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700">Promo code (optional)</label>
                    <input wire:model="code" type="text" placeholder="MORNING10" class="w-full rounded-lg border-slate-300 text-sm uppercase focus:border-amber-500 focus:ring-amber-500">
                    @error('code') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700">Discount type</label>
                    <select wire:model.live="type" class="w-full rounded-lg border-slate-300 text-sm focus:border-amber-500 focus:ring-amber-500">
                        @foreach ($types as $discountType)
                            <option value="{{ $discountType->value }}">{{ $discountType->label() }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700">{{ $type === 'percentage' ? 'Percentage (%)' : 'Amount (₹)' }}</label>
                    <input wire:model="value" type="number" step="0.01" min="0.01" class="w-full rounded-lg border-slate-300 text-sm focus:border-amber-500 focus:ring-amber-500">
                    @error('value') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700">Expiry</label>
                    <input wire:model="expiresAt" type="datetime-local" class="w-full rounded-lg border-slate-300 text-sm focus:border-amber-500 focus:ring-amber-500">
                    @error('expiresAt') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700">Quantity (max uses)</label>
                    <input wire:model="maxUses" type="number" min="1" placeholder="Unlimited" class="w-full rounded-lg border-slate-300 text-sm focus:border-amber-500 focus:ring-amber-500">
                    @error('maxUses') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700">Rule</label>
                    <select wire:model="rule" class="w-full rounded-lg border-slate-300 text-sm focus:border-amber-500 focus:ring-amber-500">
                        @foreach ($rules as $promotionRule)
                            <option value="{{ $promotionRule->value }}">{{ $promotionRule->label() }}</option>
                        @endforeach
                    </select>
                    <p class="mt-1 text-xs text-slate-500">
                        @foreach ($rules as $promotionRule)
                            @if ($rule === $promotionRule->value)
                                {{ $promotionRule->description() }}
                            @endif
                        @endforeach
                    </p>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700">Minimum order amount (₹)</label>
                    <input wire:model="minOrderAmount" type="number" step="0.01" min="0" placeholder="No minimum" class="w-full rounded-lg border-slate-300 text-sm focus:border-amber-500 focus:ring-amber-500">
                    @error('minOrderAmount') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="flex flex-wrap gap-6">
                <label class="flex items-center gap-2 text-sm text-slate-700">
                    <input wire:model="autoApply" type="checkbox" class="rounded border-slate-300 text-amber-500 focus:ring-amber-500">
                    Auto apply at POS
                </label>
                <label class="flex items-center gap-2 text-sm text-slate-700">
                    <input wire:model="isActive" type="checkbox" class="rounded border-slate-300 text-amber-500 focus:ring-amber-500">
                    Active
                </label>
            </div>

            <div class="flex flex-col gap-2 sm:flex-row">
                <button type="submit" wire:loading.attr="disabled" class="rounded-lg bg-amber-500 px-4 py-2.5 text-sm font-semibold text-white hover:bg-amber-600">Save Promotion</button>
                <button wire:click="cancel" type="button" class="rounded-lg border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700">Cancel</button>
            </div>
        </form>
    @endif

    @if ($promotions->isNotEmpty())
        <div class="space-y-3 md:hidden">
            @foreach ($promotions as $promotion)
                <article wire:key="promo-m-{{ $promotion->id }}" class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="font-semibold text-slate-900">{{ $promotion->name }}</p>
                            <p class="text-sm font-medium text-amber-600">{{ $promotion->formattedValue() }} off</p>
                            @if ($promotion->code)
                                <p class="mt-1 text-xs font-mono text-slate-500">{{ $promotion->code }}</p>
                            @endif
                        </div>
                        <span @class([
                            'rounded-full px-2 py-0.5 text-xs font-semibold',
                            'bg-emerald-100 text-emerald-700' => $promotion->is_active && ! $promotion->isExpired(),
                            'bg-slate-100 text-slate-500' => ! $promotion->is_active || $promotion->isExpired(),
                        ])>{{ $promotion->is_active && ! $promotion->isExpired() ? 'Active' : 'Inactive' }}</span>
                    </div>
                    <div class="mt-3 space-y-1 text-xs text-slate-500">
                        <p>Rule: {{ $promotion->rule->label() }}</p>
                        <p>{{ $promotion->auto_apply ? 'Auto apply' : 'Manual code' }} · Used {{ $promotion->uses_count }}{{ $promotion->max_uses ? '/'.$promotion->max_uses : '' }}</p>
                        <p>Expires: {{ $promotion->expires_at?->format('M j, Y g:i A') ?? 'Never' }}</p>
                    </div>
                    @if (auth()->user()->hasPermission('promotions.manage'))
                        <div class="mt-3 flex gap-3 border-t border-slate-100 pt-3 text-sm">
                            <button wire:click="edit({{ $promotion->id }})" type="button" class="font-medium text-amber-600">Edit</button>
                            <button wire:click="delete({{ $promotion->id }})" wire:confirm="Delete this promotion?" type="button" class="font-medium text-red-600">Delete</button>
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
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase text-slate-500">Name</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase text-slate-500">Discount</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase text-slate-500">Code</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase text-slate-500">Rule</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase text-slate-500">Auto</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase text-slate-500">Uses</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase text-slate-500">Expires</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase text-slate-500">Status</th>
                            @if (auth()->user()->hasPermission('promotions.manage'))
                                <th class="px-4 py-3 text-right text-xs font-medium uppercase text-slate-500">Actions</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($promotions as $promotion)
                            <tr wire:key="promo-{{ $promotion->id }}" class="hover:bg-slate-50">
                                <td class="px-4 py-3 text-sm font-semibold text-slate-900">{{ $promotion->name }}</td>
                                <td class="px-4 py-3 text-sm font-medium text-amber-600">{{ $promotion->formattedValue() }}</td>
                                <td class="px-4 py-3 text-sm font-mono text-slate-600">{{ $promotion->code ?? '—' }}</td>
                                <td class="px-4 py-3 text-sm text-slate-600">{{ $promotion->rule->label() }}</td>
                                <td class="px-4 py-3 text-sm text-slate-600">{{ $promotion->auto_apply ? 'Yes' : 'No' }}</td>
                                <td class="px-4 py-3 text-sm text-slate-600">{{ $promotion->uses_count }}{{ $promotion->max_uses ? ' / '.$promotion->max_uses : '' }}</td>
                                <td class="px-4 py-3 text-sm text-slate-600">{{ $promotion->expires_at?->format('M j, Y') ?? 'Never' }}</td>
                                <td class="px-4 py-3 text-sm">
                                    <span @class([
                                        'rounded-full px-2 py-0.5 text-xs font-semibold',
                                        'bg-emerald-100 text-emerald-700' => $promotion->is_active && ! $promotion->isExpired(),
                                        'bg-slate-100 text-slate-500' => ! $promotion->is_active || $promotion->isExpired(),
                                    ])>{{ $promotion->is_active && ! $promotion->isExpired() ? 'Active' : 'Inactive' }}</span>
                                </td>
                                @if (auth()->user()->hasPermission('promotions.manage'))
                                    <td class="px-4 py-3 text-right text-sm">
                                        <button wire:click="edit({{ $promotion->id }})" type="button" class="font-medium text-amber-600 hover:text-amber-700">Edit</button>
                                        <button wire:click="delete({{ $promotion->id }})" wire:confirm="Delete this promotion?" type="button" class="ml-3 font-medium text-red-600 hover:text-red-700">Delete</button>
                                    </td>
                                @endif
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </x-table-scroll>
        </div>
    @else
        <x-empty-state title="No promotions yet" description="Create your first discount to use at the POS.">
            @if (auth()->user()->hasPermission('promotions.manage'))
                <button wire:click="create" type="button" class="mt-2 rounded-lg bg-amber-500 px-4 py-2 text-sm font-semibold text-white hover:bg-amber-600">Create Discount</button>
            @endif
        </x-empty-state>
    @endif
</div>
