<div class="mx-auto max-w-7xl space-y-4 sm:space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-xl font-bold text-slate-900 sm:text-2xl">Expenses</h1>
            <p class="text-sm text-slate-500">Total: <span class="font-semibold text-amber-600">₹{{ number_format((float) $totalExpenses, 2) }}</span></p>
        </div>
        <button wire:click="create" type="button" class="w-full rounded-lg bg-amber-500 px-4 py-2.5 text-sm font-semibold text-white hover:bg-amber-600 sm:w-auto">Add Expense</button>
    </div>

    @if (session('success'))
        <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">{{ session('success') }}</div>
    @endif

    @if ($showForm)
        <form wire:submit="save" class="space-y-4 rounded-xl border border-slate-200 bg-white p-4 shadow-sm sm:p-6">
            <h2 class="text-lg font-semibold text-slate-900">{{ $editingId ? 'Edit Expense' : 'New Expense' }}</h2>
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700">Title</label>
                    <input wire:model="title" type="text" class="w-full rounded-lg border-slate-300 text-sm focus:border-amber-500 focus:ring-amber-500">
                    @error('title') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700">Category</label>
                    <input wire:model="category" type="text" placeholder="Utilities, Supplies..." class="w-full rounded-lg border-slate-300 text-sm focus:border-amber-500 focus:ring-amber-500">
                    @error('category') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700">Amount (₹)</label>
                    <input wire:model="amount" type="number" step="0.01" min="0.01" class="w-full rounded-lg border-slate-300 text-sm focus:border-amber-500 focus:ring-amber-500">
                    @error('amount') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700">Date</label>
                    <input wire:model="expense_date" type="date" class="w-full rounded-lg border-slate-300 text-sm focus:border-amber-500 focus:ring-amber-500">
                </div>
                <div class="sm:col-span-2">
                    <label class="mb-1 block text-sm font-medium text-slate-700">Notes</label>
                    <textarea wire:model="notes" rows="2" class="w-full rounded-lg border-slate-300 text-sm focus:border-amber-500 focus:ring-amber-500"></textarea>
                </div>
            </div>
            <div class="flex flex-col gap-2 sm:flex-row">
                <button type="submit" wire:loading.attr="disabled" class="rounded-lg bg-amber-500 px-4 py-2.5 text-sm font-semibold text-white hover:bg-amber-600">Save</button>
                <button wire:click="cancel" type="button" class="rounded-lg border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700">Cancel</button>
            </div>
        </form>
    @endif

    @if ($expenses->isNotEmpty())
        {{-- Mobile cards --}}
        <div class="space-y-3 md:hidden">
            @foreach ($expenses as $expense)
                <article wire:key="expense-m-{{ $expense->id }}" class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="font-semibold text-slate-900">{{ $expense->title }}</p>
                            <p class="text-sm text-slate-500">{{ $expense->expense_date->format('M j, Y') }} · {{ $expense->category }}</p>
                        </div>
                        <p class="shrink-0 text-lg font-bold text-amber-600">₹{{ number_format((float) $expense->amount, 2) }}</p>
                    </div>
                    <div class="mt-3 flex gap-4 border-t border-slate-100 pt-3 text-sm">
                        <button wire:click="edit({{ $expense->id }})" type="button" class="font-medium text-amber-600 hover:text-amber-700">Edit</button>
                        <button wire:click="delete({{ $expense->id }})" wire:confirm="Delete this expense?" type="button" class="font-medium text-red-600 hover:text-red-700">Delete</button>
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
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase text-slate-500">Date</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase text-slate-500">Title</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase text-slate-500">Category</th>
                            <th class="px-4 py-3 text-right text-xs font-medium uppercase text-slate-500">Amount</th>
                            <th class="px-4 py-3 text-right text-xs font-medium uppercase text-slate-500">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($expenses as $expense)
                            <tr wire:key="expense-{{ $expense->id }}" class="hover:bg-slate-50">
                                <td class="px-4 py-3 text-sm text-slate-500">{{ $expense->expense_date->format('M j, Y') }}</td>
                                <td class="px-4 py-3 text-sm font-medium text-slate-900">{{ $expense->title }}</td>
                                <td class="px-4 py-3 text-sm text-slate-600">{{ $expense->category }}</td>
                                <td class="px-4 py-3 text-right text-sm font-semibold text-amber-600">₹{{ number_format((float) $expense->amount, 2) }}</td>
                                <td class="px-4 py-3 text-right text-sm">
                                    <button wire:click="edit({{ $expense->id }})" type="button" class="font-medium text-amber-600 hover:text-amber-700">Edit</button>
                                    <button wire:click="delete({{ $expense->id }})" wire:confirm="Delete this expense?" type="button" class="ml-3 font-medium text-red-600 hover:text-red-700">Delete</button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </x-table-scroll>
        </div>
    @else
        <x-empty-state title="No expenses recorded" description="Track your restaurant expenses here.">
            <button wire:click="create" type="button" class="mt-2 rounded-lg bg-amber-500 px-4 py-2 text-sm font-semibold text-white hover:bg-amber-600">Add Expense</button>
        </x-empty-state>
    @endif
</div>
