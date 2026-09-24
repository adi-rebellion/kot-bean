@props([
    'label',
    'value',
    'icon' => null,
    'trend' => null,
    'trendUp' => true,
])

<div {{ $attributes->merge(['class' => 'rounded-xl border border-slate-200 bg-white p-5 shadow-sm']) }}>
    <div class="flex items-start justify-between">
        <div>
            <p class="text-sm font-medium text-slate-500">{{ $label }}</p>
            <p class="mt-2 text-2xl font-semibold tracking-tight text-slate-900">{{ $value }}</p>
            @if($trend)
                <p @class([
                    'mt-1 text-xs font-medium',
                    'text-emerald-600' => $trendUp,
                    'text-red-600' => ! $trendUp,
                ])>{{ $trend }}</p>
            @endif
        </div>
        @if($icon)
            <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-amber-50 text-amber-600">
                {!! $icon !!}
            </div>
        @endif
    </div>
</div>
