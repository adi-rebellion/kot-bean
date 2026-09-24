<div class="mx-auto max-w-7xl space-y-4 sm:space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <h1 class="text-xl font-bold text-slate-900 sm:text-2xl">Staff</h1>
        <button wire:click="create" type="button" class="w-full rounded-lg bg-amber-500 px-4 py-2.5 text-sm font-semibold text-white hover:bg-amber-600 sm:w-auto">Add Staff</button>
    </div>

    @if ($generatedPassword)
        <div class="rounded-xl border border-amber-200 bg-amber-50 p-4">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <p class="font-semibold text-amber-900">Staff account created</p>
                    <p class="mt-1 text-sm text-amber-800">
                        Share these login details with <span class="font-medium">{{ $createdStaffName }}</span>.
                        They can sign in using their mobile number or the generated email.
                    </p>
                    <dl class="mt-3 space-y-2 text-sm">
                        <div class="flex flex-wrap gap-x-2">
                            <dt class="font-medium text-amber-900">Mobile:</dt>
                            <dd class="font-mono text-amber-950">{{ $createdStaffPhone }}</dd>
                        </div>
                        <div class="flex flex-wrap gap-x-2">
                            <dt class="font-medium text-amber-900">Password:</dt>
                            <dd class="font-mono text-amber-950">{{ $generatedPassword }}</dd>
                        </div>
                    </dl>
                    <p class="mt-2 text-xs text-amber-700">This password is shown only once. Copy it now before closing.</p>
                </div>
                <button wire:click="dismissCredentials" type="button" class="shrink-0 rounded-lg px-2 py-1 text-xs font-medium text-amber-700 hover:bg-amber-100">Dismiss</button>
            </div>
        </div>
    @endif

    @if ($showForm)
        <form wire:submit="save" class="space-y-4 rounded-xl border border-slate-200 bg-white p-4 shadow-sm sm:p-6">
            <h2 class="text-lg font-semibold text-slate-900">New Staff Member</h2>
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700">Name</label>
                    <input wire:model="name" type="text" class="w-full rounded-lg border-slate-300 text-sm focus:border-amber-500 focus:ring-amber-500">
                    @error('name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700">Mobile Number</label>
                    <input wire:model="phone" type="tel" inputmode="tel" placeholder="9876543210" class="w-full rounded-lg border-slate-300 text-sm focus:border-amber-500 focus:ring-amber-500">
                    @error('phone') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div class="sm:col-span-2">
                    <label class="mb-1 block text-sm font-medium text-slate-700">Role</label>
                    <select wire:model="role_id" class="w-full rounded-lg border-slate-300 text-sm focus:border-amber-500 focus:ring-amber-500">
                        <option value="">Select role</option>
                        @foreach ($roles as $role)
                            <option value="{{ $role->id }}">{{ $role->name }}</option>
                        @endforeach
                    </select>
                    @error('role_id') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>
            <p class="text-xs text-slate-500">A random password will be generated automatically. Share it with the staff member after creation.</p>
            <div class="flex flex-col gap-2 sm:flex-row">
                <button type="submit" wire:loading.attr="disabled" class="rounded-lg bg-amber-500 px-4 py-2.5 text-sm font-semibold text-white hover:bg-amber-600 disabled:opacity-50">Create Staff</button>
                <button wire:click="cancel" type="button" class="rounded-lg border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700">Cancel</button>
            </div>
        </form>
    @endif

    @if ($staff->isNotEmpty())
        {{-- Mobile cards --}}
        <div class="space-y-3 md:hidden">
            @foreach ($staff as $member)
                <article wire:key="staff-m-{{ $member->id }}" class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="font-semibold text-slate-900">{{ $member->name }}</p>
                            <p class="truncate text-sm text-slate-500">{{ $member->email }}</p>
                        </div>
                        <span @class([
                            'shrink-0 inline-flex rounded-full px-2 py-0.5 text-xs font-medium',
                            'bg-emerald-50 text-emerald-700' => $member->is_active,
                            'bg-slate-100 text-slate-600' => ! $member->is_active,
                        ])>{{ $member->is_active ? 'Active' : 'Inactive' }}</span>
                    </div>
                    <div class="mt-3 grid grid-cols-2 gap-2 text-sm">
                        <div>
                            <p class="text-xs text-slate-400">Phone</p>
                            <p class="text-slate-700">{{ $member->phone ?? '—' }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-slate-400">Role</p>
                            <p class="text-slate-700">{{ $member->role?->name ?? '—' }}</p>
                        </div>
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
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase text-slate-500">Email</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase text-slate-500">Phone</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase text-slate-500">Role</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase text-slate-500">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($staff as $member)
                            <tr wire:key="staff-{{ $member->id }}" class="hover:bg-slate-50">
                                <td class="px-4 py-3 text-sm font-semibold text-slate-900">{{ $member->name }}</td>
                                <td class="px-4 py-3 text-sm text-slate-600">{{ $member->email }}</td>
                                <td class="px-4 py-3 text-sm text-slate-600">{{ $member->phone ?? '—' }}</td>
                                <td class="px-4 py-3 text-sm text-slate-600">{{ $member->role?->name ?? '—' }}</td>
                                <td class="px-4 py-3">
                                    <span @class([
                                        'inline-flex rounded-full px-2 py-0.5 text-xs font-medium',
                                        'bg-emerald-50 text-emerald-700' => $member->is_active,
                                        'bg-slate-100 text-slate-600' => ! $member->is_active,
                                    ])>{{ $member->is_active ? 'Active' : 'Inactive' }}</span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </x-table-scroll>
        </div>
    @else
        <x-empty-state title="No staff members" description="Add your first staff member using their mobile number.">
            <button wire:click="create" type="button" class="mt-2 inline-flex rounded-lg bg-amber-500 px-4 py-2 text-sm font-semibold text-white hover:bg-amber-600">Add Staff</button>
        </x-empty-state>
    @endif
</div>
