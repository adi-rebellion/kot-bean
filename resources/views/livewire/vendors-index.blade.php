<div class="mx-auto max-w-7xl space-y-4 sm:space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-xl font-bold text-slate-900 sm:text-2xl">Vendors</h1>
            <p class="text-sm text-slate-500">Outstanding: <span class="font-semibold text-amber-600">₹{{ number_format($summary['total_pending'], 2) }}</span></p>
        </div>
        <div class="flex flex-col gap-2 sm:flex-row">
            <a href="{{ route('vendors.ledger') }}" wire:navigate class="inline-flex items-center justify-center rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">Vendor Ledger</a>
            @if (auth()->user()->hasPermission('expenses.manage'))
                <button wire:click="create" type="button" class="rounded-lg bg-amber-500 px-4 py-2.5 text-sm font-semibold text-white hover:bg-amber-600">Add Vendor</button>
            @endif
        </div>
    </div>

    @if (session('success'))
        <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">{{ session('success') }}</div>
    @endif

    @if ($showForm)
        <form wire:submit="save" class="space-y-4 rounded-xl border border-slate-200 bg-white p-4 shadow-sm sm:p-6">
            <h2 class="text-lg font-semibold text-slate-900">{{ $editingId ? 'Edit Vendor' : 'New Vendor' }}</h2>
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700">Name</label>
                    <input wire:model="name" type="text" class="w-full rounded-lg border-slate-300 text-sm focus:border-amber-500 focus:ring-amber-500">
                    @error('name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700">Phone</label>
                    <input wire:model="phone" type="tel" class="w-full rounded-lg border-slate-300 text-sm focus:border-amber-500 focus:ring-amber-500">
                    @error('phone') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700">Email</label>
                    <input wire:model="email" type="email" class="w-full rounded-lg border-slate-300 text-sm focus:border-amber-500 focus:ring-amber-500">
                    @error('email') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div class="sm:col-span-2">
                    <label class="mb-1 block text-sm font-medium text-slate-700">Address</label>
                    <textarea wire:model="address" rows="2" class="w-full rounded-lg border-slate-300 text-sm focus:border-amber-500 focus:ring-amber-500"></textarea>
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

    @if ($vendors->isNotEmpty())
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($vendors as $vendor)
                <article wire:key="vendor-{{ $vendor->id }}" class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="font-semibold text-slate-900">{{ $vendor->name }}</p>
                            <p class="text-sm text-slate-500">{{ $vendor->phone ?? 'No phone' }}</p>
                            @if ($vendor->email)
                                <p class="truncate text-sm text-slate-500">{{ $vendor->email }}</p>
                            @endif
                        </div>
                        <span class="shrink-0 rounded-full bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-600">{{ $vendor->expenses_count }} bills</span>
                    </div>
                    <div class="mt-3 flex flex-wrap gap-3 text-sm">
                        <a href="{{ route('vendors.ledger', ['vendorFilter' => $vendor->id]) }}" wire:navigate class="font-medium text-amber-600 hover:text-amber-700">View ledger</a>
                        @if (auth()->user()->hasPermission('expenses.manage'))
                            <button wire:click="edit({{ $vendor->id }})" type="button" class="font-medium text-slate-600 hover:text-slate-800">Edit</button>
                            <button wire:click="delete({{ $vendor->id }})" wire:confirm="Delete this vendor?" type="button" class="font-medium text-red-600 hover:text-red-700">Delete</button>
                        @endif
                    </div>
                </article>
            @endforeach
        </div>
    @else
        <x-empty-state title="No vendors yet" description="Add suppliers and map them to expenses for payment tracking.">
            @if (auth()->user()->hasPermission('expenses.manage'))
                <button wire:click="create" type="button" class="mt-2 rounded-lg bg-amber-500 px-4 py-2 text-sm font-semibold text-white hover:bg-amber-600">Add Vendor</button>
            @endif
        </x-empty-state>
    @endif
</div>
