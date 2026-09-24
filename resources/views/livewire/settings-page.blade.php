<div class="mx-auto max-w-3xl space-y-4 sm:space-y-6">
    <div>
        <h1 class="text-xl font-bold text-slate-900 dark:text-white sm:text-2xl">Restaurant Settings</h1>
        <p class="mt-1 text-sm text-slate-500">Manage your business profile, branding, and preferences.</p>
    </div>

    @if (session('success'))
        <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">{{ session('success') }}</div>
    @endif

    <form wire:submit="save" class="space-y-6">
        {{-- Branding --}}
        <section class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900 sm:p-6">
            <h2 class="text-lg font-semibold text-slate-900 dark:text-white">Branding</h2>
            <p class="mt-1 text-sm text-slate-500">Your logo and theme color appear across the app sidebar, POS, and receipts.</p>

            <div class="mt-6 grid gap-6 lg:grid-cols-2">
                {{-- Logo upload --}}
                <div>
                    <label class="mb-2 block text-sm font-medium text-slate-700 dark:text-slate-300">Business Logo</label>
                    <div class="flex flex-col items-start gap-4 sm:flex-row">
                        <div class="flex h-24 w-24 shrink-0 items-center justify-center overflow-hidden rounded-2xl border border-slate-200 bg-slate-50 dark:border-slate-700 dark:bg-slate-800">
                            @if ($logo)
                                <img src="{{ $logo->temporaryUrl() }}" alt="Logo preview" class="h-full w-full object-contain">
                            @elseif ($current_logo_url)
                                <img src="{{ $current_logo_url }}" alt="Current logo" class="h-full w-full object-contain">
                            @else
                                <svg class="h-10 w-10 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909M3.75 21h16.5A2.25 2.25 0 0022.5 18.75V5.25A2.25 2.25 0 0020.25 3H3.75A2.25 2.25 0 001.5 5.25v13.5A2.25 2.25 0 003.75 21z"/></svg>
                            @endif
                        </div>
                        <div class="min-w-0 flex-1 space-y-2">
                            <input
                                wire:model="logo"
                                type="file"
                                accept="image/png,image/jpeg,image/webp,image/svg+xml"
                                class="block w-full text-sm text-slate-500 file:mr-3 file:rounded-lg file:border-0 file:bg-slate-100 file:px-3 file:py-2 file:text-sm file:font-semibold file:text-slate-700 hover:file:bg-slate-200 dark:file:bg-slate-800 dark:file:text-slate-200"
                            >
                            <p class="text-xs text-slate-500">PNG, JPG, WebP or SVG. Max 2 MB. Square logos work best.</p>
                            @if ($current_logo_url || $logo)
                                <button wire:click.prevent="removeLogo" type="button" class="text-xs font-medium text-red-600 hover:text-red-700">Remove logo</button>
                            @endif
                            @error('logo') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                            <div wire:loading wire:target="logo" class="text-xs text-slate-500">Uploading preview…</div>
                        </div>
                    </div>
                </div>

                {{-- Theme color --}}
                <div>
                    <label class="mb-2 block text-sm font-medium text-slate-700 dark:text-slate-300">Theme Color</label>
                    <div class="flex items-center gap-3">
                        <input
                            wire:model.live="theme_color"
                            type="color"
                            class="h-12 w-14 cursor-pointer rounded-lg border border-slate-200 bg-white p-1 dark:border-slate-700 dark:bg-slate-800"
                        >
                        <input
                            wire:model.live="theme_color"
                            type="text"
                            maxlength="7"
                            class="w-full rounded-lg border-slate-300 font-mono text-sm uppercase focus-brand focus:ring-2 dark:border-slate-700 dark:bg-slate-800 dark:text-white"
                            placeholder="#F59E0B"
                        >
                    </div>
                    @error('theme_color') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror

                    <p class="mt-3 text-xs font-medium uppercase tracking-wide text-slate-500">Presets</p>
                    <div class="mt-2 flex flex-wrap gap-2">
                        @foreach ($themePresets as $label => $hex)
                            <button
                                wire:key="theme-{{ $label }}"
                                wire:click="selectThemeColor('{{ $hex }}')"
                                type="button"
                                title="{{ ucfirst($label) }}"
                                class="h-9 w-9 rounded-full border-2 transition hover:scale-110 {{ $theme_color === $hex ? 'border-slate-900 ring-2 ring-offset-2 dark:border-white' : 'border-transparent' }}"
                                style="background-color: {{ $hex }}; --tw-ring-color: {{ $hex }};"
                            ></button>
                        @endforeach
                    </div>

                    <div class="mt-4 rounded-lg border border-slate-200 p-3 dark:border-slate-700">
                        <p class="mb-2 text-xs font-medium text-slate-500">Preview</p>
                        <div class="flex items-center gap-3">
                            <span class="rounded-lg px-3 py-1.5 text-sm font-bold text-white" style="background-color: {{ $theme_color }}">Primary button</span>
                            <span class="rounded-lg px-3 py-1.5 text-sm font-semibold" style="background-color: color-mix(in srgb, {{ $theme_color }} 16%, white); color: {{ $theme_color }}">Active nav</span>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        {{-- Business details --}}
        <section class="space-y-6 rounded-xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900 sm:p-6">
            <h2 class="text-lg font-semibold text-slate-900 dark:text-white">Business Details</h2>

            <div>
                <label class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300">Restaurant Name</label>
                <input wire:model="name" type="text" class="w-full rounded-lg border-slate-300 text-sm focus-brand focus:ring-2 dark:border-slate-700 dark:bg-slate-800 dark:text-white">
                @error('name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300">Address</label>
                <textarea wire:model="address" rows="3" class="w-full rounded-lg border-slate-300 text-sm focus-brand focus:ring-2 dark:border-slate-700 dark:bg-slate-800 dark:text-white"></textarea>
            </div>
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300">Phone</label>
                    <input wire:model="phone" type="text" class="w-full rounded-lg border-slate-300 text-sm focus-brand focus:ring-2 dark:border-slate-700 dark:bg-slate-800 dark:text-white">
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300">Email</label>
                    <input wire:model="email" type="email" class="w-full rounded-lg border-slate-300 text-sm focus-brand focus:ring-2 dark:border-slate-700 dark:bg-slate-800 dark:text-white">
                </div>
            </div>
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300">GSTIN</label>
                    <input wire:model="gstin" type="text" class="w-full rounded-lg border-slate-300 text-sm focus-brand focus:ring-2 dark:border-slate-700 dark:bg-slate-800 dark:text-white">
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300">Default Tax Rate (%)</label>
                    <input wire:model="default_tax_rate" type="number" step="0.01" min="0" max="100" class="w-full rounded-lg border-slate-300 text-sm focus-brand focus:ring-2 dark:border-slate-700 dark:bg-slate-800 dark:text-white">
                </div>
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300">Timezone</label>
                <input wire:model="timezone" type="text" class="w-full rounded-lg border-slate-300 text-sm focus-brand focus:ring-2 dark:border-slate-700 dark:bg-slate-800 dark:text-white">
            </div>
            <div class="rounded-lg border border-slate-200 bg-slate-50 p-4 dark:border-slate-700 dark:bg-slate-800/50">
                <label class="flex cursor-pointer items-start gap-3">
                    <input wire:model="tables_enabled" type="checkbox" class="mt-1 rounded border-slate-300 focus-brand dark:border-slate-600" style="accent-color: {{ $theme_color }}">
                    <span>
                        <span class="block text-sm font-medium text-slate-900 dark:text-white">Enable table management</span>
                        <span class="mt-1 block text-sm text-slate-500">Turn off for food carts and stalls without seating.</span>
                    </span>
                </label>
            </div>
        </section>

        <div class="flex justify-stretch sm:justify-end">
            <button type="submit" wire:loading.attr="disabled" class="w-full rounded-lg bg-brand px-6 py-2.5 text-sm font-bold text-white hover:bg-brand-hover disabled:opacity-50 sm:w-auto">
                <span wire:loading.remove wire:target="save">Save Settings</span>
                <span wire:loading wire:target="save">Saving...</span>
            </button>
        </div>
    </form>
</div>
