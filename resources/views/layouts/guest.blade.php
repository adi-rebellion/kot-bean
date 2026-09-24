<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        @include('partials.pwa-head')

        <title>{{ $title ? $title.' — KotBean' : config('app.name', 'KotBean') }}</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=dm-sans:400,500,600,700&family=instrument-sans:500,600,700" rel="stylesheet">

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="guest-auth font-sans antialiased text-zinc-900 selection:bg-amber-200/80 selection:text-amber-950">
        <div class="relative min-h-screen overflow-hidden bg-zinc-50">
            <div class="landing-orb landing-orb--1"></div>
            <div class="landing-orb landing-orb--2"></div>
            <div class="landing-noise pointer-events-none absolute inset-0 opacity-[0.28]"></div>

            <div class="relative mx-auto grid min-h-screen max-w-6xl lg:grid-cols-2">
                <aside class="hidden flex-col justify-between px-10 py-10 lg:flex xl:px-14">
                    <a href="{{ route('home') }}" class="inline-flex items-center gap-2.5">
                        <span class="relative flex h-9 w-9 items-center justify-center overflow-hidden rounded-xl bg-zinc-900 shadow-lg">
                            <span class="absolute inset-0 bg-gradient-to-br from-amber-400 to-orange-600 opacity-90"></span>
                            <span class="relative text-xs font-bold tracking-tight text-white">KB</span>
                        </span>
                        <span class="text-[15px] font-semibold tracking-tight text-zinc-900">KotBean</span>
                    </a>

                    <div class="max-w-md">
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-amber-700">Restaurant OS</p>
                        <h1 class="landing-display mt-4 text-4xl font-semibold tracking-tight text-zinc-950">
                            Run the floor, kitchen, and close
                            <span class="landing-gradient-text">from one place</span>
                        </h1>
                        <p class="mt-4 text-sm leading-relaxed text-zinc-600">
                            POS, live KOT, inventory, loyalty, and daily cash close — built for Indian cafés and restaurants.
                        </p>

                        <ul class="mt-8 space-y-3 text-sm text-zinc-600">
                            <li class="flex items-center gap-2.5">
                                <span class="flex h-6 w-6 items-center justify-center rounded-full bg-white text-emerald-600 shadow-sm ring-1 ring-zinc-200">
                                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                </span>
                                GST-ready invoices
                            </li>
                            <li class="flex items-center gap-2.5">
                                <span class="flex h-6 w-6 items-center justify-center rounded-full bg-white text-emerald-600 shadow-sm ring-1 ring-zinc-200">
                                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                </span>
                                Kitchen tickets in under 2 seconds
                            </li>
                            <li class="flex items-center gap-2.5">
                                <span class="flex h-6 w-6 items-center justify-center rounded-full bg-white text-emerald-600 shadow-sm ring-1 ring-zinc-200">
                                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                </span>
                                Works on phone, tablet, and desktop
                            </li>
                        </ul>
                    </div>

                    <p class="text-xs text-zinc-400">&copy; {{ date('Y') }} KotBean</p>
                </aside>

                <main class="flex items-center justify-center px-4 py-10 sm:px-8">
                    <div class="w-full max-w-md">
                        <a href="{{ route('home') }}" class="mb-8 inline-flex items-center gap-2.5 lg:hidden">
                            <span class="relative flex h-9 w-9 items-center justify-center overflow-hidden rounded-xl bg-zinc-900 shadow-lg">
                                <span class="absolute inset-0 bg-gradient-to-br from-amber-400 to-orange-600 opacity-90"></span>
                                <span class="relative text-xs font-bold tracking-tight text-white">KB</span>
                            </span>
                            <span class="text-[15px] font-semibold tracking-tight text-zinc-900">KotBean</span>
                        </a>

                        <div class="rounded-[1.75rem] border border-zinc-200/80 bg-white/90 p-6 shadow-2xl shadow-zinc-900/10 backdrop-blur sm:p-8">
                            {{ $slot }}
                        </div>
                    </div>
                </main>
            </div>
        </div>
    </body>
</html>
