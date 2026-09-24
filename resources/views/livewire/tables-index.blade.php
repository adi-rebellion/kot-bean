<div class="mx-auto max-w-7xl space-y-4 sm:space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <h1 class="text-xl font-bold text-slate-900 sm:text-2xl">Tables</h1>
        <div class="flex flex-wrap gap-x-4 gap-y-2 text-xs">
            @foreach (\App\Enums\TableStatus::cases() as $status)
                @php
                    $dotColor = match ($status->color()) {
                        'emerald' => 'bg-emerald-500',
                        'amber' => 'bg-amber-500',
                        'blue' => 'bg-blue-500',
                        'purple' => 'bg-purple-500',
                        default => 'bg-slate-400',
                    };
                @endphp
                <span class="flex items-center gap-1.5 text-slate-600">
                    <span class="h-2.5 w-2.5 rounded-full {{ $dotColor }}"></span>
                    {{ $status->label() }}
                </span>
            @endforeach
        </div>
    </div>

    @foreach ($tableGroups as $zone => $tables)
        <div wire:key="zone-{{ $zone }}">
            <h2 class="mb-3 text-sm font-semibold uppercase tracking-wide text-slate-500">{{ $zone }}</h2>
            <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 sm:gap-4 md:grid-cols-4 lg:grid-cols-6">
                @foreach ($tables as $table)
                    @php
                        $bgColor = match ($table->status->color()) {
                            'emerald' => 'border-emerald-300 bg-emerald-50',
                            'amber' => 'border-amber-300 bg-amber-50',
                            'blue' => 'border-blue-300 bg-blue-50',
                            'purple' => 'border-purple-300 bg-purple-50',
                            default => 'border-slate-200 bg-white',
                        };
                    @endphp
                    <div wire:key="table-{{ $table->id }}" class="rounded-xl border-2 p-4 shadow-sm {{ $bgColor }}">
                        <div class="text-center">
                            <p class="text-lg font-bold text-slate-900">{{ $table->name }}</p>
                            <p class="text-xs text-slate-500">{{ $table->capacity }} seats</p>
                            <p class="mt-2 text-sm font-semibold text-slate-700">{{ $table->status->label() }}</p>
                        </div>
                        <select
                            wire:change="updateStatus({{ $table->id }}, $event.target.value)"
                            class="mt-3 w-full rounded-lg border-slate-300 text-xs focus:border-amber-500 focus:ring-amber-500"
                        >
                            @foreach ($statuses as $status)
                                <option value="{{ $status->value }}" @selected($table->status === $status)>{{ $status->label() }}</option>
                            @endforeach
                        </select>
                    </div>
                @endforeach
            </div>
        </div>
    @endforeach

    @if ($tableGroups->isEmpty())
        <x-empty-state title="No tables configured" description="Add tables in settings to manage your floor plan." />
    @endif
</div>
