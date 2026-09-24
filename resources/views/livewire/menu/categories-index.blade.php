<div class="mx-auto max-w-7xl space-y-4 sm:space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-xl font-bold text-slate-900 sm:text-2xl">Categories</h1>
            <p class="text-sm text-slate-500">Organize your menu into categories.</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('menu.index') }}" wire:navigate class="inline-flex items-center justify-center rounded-lg border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">Products</a>
            @if (auth()->user()->hasPermission('menu.manage'))
                <button wire:click="create" type="button" class="rounded-lg bg-amber-500 px-4 py-2.5 text-sm font-semibold text-white hover:bg-amber-600">Add Category</button>
            @endif
        </div>
    </div>

    @if (session('success'))
        <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{{ session('error') }}</div>
    @endif

    @if ($showForm)
        <form wire:submit="save" class="space-y-4 rounded-xl border border-slate-200 bg-white p-4 shadow-sm sm:p-6">
            <h2 class="text-lg font-semibold text-slate-900">{{ $editingId ? 'Edit Category' : 'New Category' }}</h2>
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700">Name</label>
                    <input wire:model="name" type="text" class="w-full rounded-lg border-slate-300 text-sm focus:border-amber-500 focus:ring-amber-500">
                    @error('name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700">Sort order</label>
                    <input wire:model="sortOrder" type="number" min="0" class="w-full rounded-lg border-slate-300 text-sm focus:border-amber-500 focus:ring-amber-500">
                </div>
                <div class="sm:col-span-2">
                    <label class="mb-1 block text-sm font-medium text-slate-700">Description</label>
                    <textarea wire:model="description" rows="2" class="w-full rounded-lg border-slate-300 text-sm focus:border-amber-500 focus:ring-amber-500"></textarea>
                </div>
            </div>
            <label class="flex items-center gap-2 text-sm text-slate-700">
                <input wire:model="isActive" type="checkbox" class="rounded border-slate-300 text-amber-500 focus:ring-amber-500">
                Active
            </label>
            <div class="flex gap-2">
                <button type="submit" class="rounded-lg bg-amber-500 px-4 py-2.5 text-sm font-semibold text-white hover:bg-amber-600">Save</button>
                <button wire:click="cancel" type="button" class="rounded-lg border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700">Cancel</button>
            </div>
        </form>
    @endif

    @if ($categories->isNotEmpty())
        <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
            <x-table-scroll>
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase text-slate-500">Name</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase text-slate-500">Products</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase text-slate-500">Sort</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase text-slate-500">Status</th>
                            @if (auth()->user()->hasPermission('menu.manage'))
                                <th class="px-4 py-3 text-right text-xs font-medium uppercase text-slate-500">Actions</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($categories as $category)
                            <tr wire:key="cat-{{ $category->id }}" class="hover:bg-slate-50">
                                <td class="px-4 py-3 text-sm font-semibold text-slate-900">{{ $category->name }}</td>
                                <td class="px-4 py-3 text-sm text-slate-600">{{ $category->products_count }}</td>
                                <td class="px-4 py-3 text-sm text-slate-600">{{ $category->sort_order }}</td>
                                <td class="px-4 py-3 text-sm">{{ $category->is_active ? 'Active' : 'Inactive' }}</td>
                                @if (auth()->user()->hasPermission('menu.manage'))
                                    <td class="px-4 py-3 text-right text-sm">
                                        <button wire:click="edit({{ $category->id }})" type="button" class="font-medium text-amber-600">Edit</button>
                                        <button wire:click="delete({{ $category->id }})" wire:confirm="Delete this category?" type="button" class="ml-3 font-medium text-red-600">Delete</button>
                                    </td>
                                @endif
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </x-table-scroll>
        </div>
    @else
        <x-empty-state title="No categories yet" description="Create categories to organize your menu." />
    @endif
</div>
