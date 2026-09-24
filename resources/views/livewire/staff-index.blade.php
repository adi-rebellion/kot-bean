<div class="mx-auto max-w-7xl space-y-4 sm:space-y-6">
    <h1 class="text-xl font-bold text-slate-900 sm:text-2xl">Staff</h1>

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
        <x-empty-state title="No staff members" description="Staff users will appear here once added." />
    @endif
</div>
