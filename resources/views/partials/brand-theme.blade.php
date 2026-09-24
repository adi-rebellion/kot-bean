@php
    $brandColor = auth()->user()?->restaurant?->themeColor() ?? '#f59e0b';
@endphp
<style>
    :root {
        --brand-color: {{ $brandColor }};
        --brand-color-hover: color-mix(in srgb, var(--brand-color) 82%, black);
        --brand-color-soft: color-mix(in srgb, var(--brand-color) 16%, white);
        --brand-color-muted: color-mix(in srgb, var(--brand-color) 28%, transparent);
    }

    .dark {
        --brand-color-soft: color-mix(in srgb, var(--brand-color) 22%, transparent);
    }
</style>
