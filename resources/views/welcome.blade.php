@extends('layouts.marketing')

@section('title', 'KotBean — The Modern Restaurant Operating System')

@section('content')
    {{-- Nav --}}
    <div class="fixed inset-x-0 top-0 z-50 flex justify-center px-4 pt-4 sm:px-6">
        <header id="landing-nav" class="landing-nav flex w-full max-w-5xl items-center justify-between rounded-2xl px-4 py-3 sm:px-5">
            <a href="{{ route('home') }}" class="flex items-center gap-2.5">
                <span class="relative flex h-9 w-9 items-center justify-center overflow-hidden rounded-xl bg-zinc-900 shadow-lg">
                    <span class="absolute inset-0 bg-gradient-to-br from-amber-400 to-orange-600 opacity-90"></span>
                    <span class="relative text-xs font-bold tracking-tight text-white">KB</span>
                </span>
                <span class="text-[15px] font-semibold tracking-tight text-zinc-900">KotBean</span>
            </a>

            <nav class="hidden items-center gap-1 md:flex">
                @foreach (['Features' => '#features', 'Platform' => '#platform', 'Pricing' => '#pricing'] as $label => $href)
                    <a href="{{ $href }}" data-scroll class="rounded-lg px-3.5 py-2 text-[13px] font-medium text-zinc-600 transition hover:bg-zinc-100 hover:text-zinc-900">{{ $label }}</a>
                @endforeach
            </nav>

            <div class="hidden items-center gap-2 md:flex">
                @auth
                    <a href="{{ route('dashboard') }}" class="rounded-xl bg-zinc-900 px-4 py-2 text-[13px] font-semibold text-white transition hover:bg-zinc-800">Dashboard</a>
                @else
                    <a href="{{ route('login') }}" class="rounded-lg px-3.5 py-2 text-[13px] font-medium text-zinc-600 transition hover:text-zinc-900">Sign in</a>
                    <a href="{{ route('register') }}" class="landing-btn-primary rounded-xl px-4 py-2 text-[13px] font-semibold text-zinc-950">Start free</a>
                @endauth
            </div>

            <button id="mobile-menu-toggle" type="button" class="rounded-lg p-2 text-zinc-600 md:hidden" aria-label="Menu">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
            </button>
        </header>
    </div>
    <div id="mobile-menu" class="fixed inset-x-4 top-[4.5rem] z-40 hidden rounded-2xl border border-zinc-200/80 bg-white/95 p-4 shadow-2xl backdrop-blur-xl md:hidden">
        <div class="flex flex-col gap-1">
            @foreach (['Features' => '#features', 'Platform' => '#platform', 'Pricing' => '#pricing'] as $label => $href)
                <a href="{{ $href }}" data-scroll class="rounded-xl px-3 py-2.5 text-sm font-medium text-zinc-700">{{ $label }}</a>
            @endforeach
            <div class="mt-2 grid grid-cols-2 gap-2 border-t border-zinc-100 pt-3">
                @auth
                    <a href="{{ route('dashboard') }}" class="col-span-2 rounded-xl bg-zinc-900 py-2.5 text-center text-sm font-semibold text-white">Dashboard</a>
                @else
                    <a href="{{ route('login') }}" class="rounded-xl border border-zinc-200 py-2.5 text-center text-sm font-medium text-zinc-700">Sign in</a>
                    <a href="{{ route('register') }}" class="landing-btn-primary rounded-xl py-2.5 text-center text-sm font-semibold text-zinc-950">Start free</a>
                @endauth
            </div>
        </div>
    </div>

    {{-- Hero --}}
    <section class="landing-hero relative overflow-hidden pt-28 pb-16 sm:pt-36 sm:pb-24">
        <div class="landing-orb landing-orb--1"></div>
        <div class="landing-orb landing-orb--2"></div>
        <div class="landing-noise pointer-events-none absolute inset-0 opacity-[0.35]"></div>

        <div class="relative mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">
            <div class="mx-auto max-w-3xl text-center landing-reveal">
                <div class="mb-6 inline-flex items-center gap-2 rounded-full border border-zinc-200/80 bg-white/70 px-3 py-1.5 text-xs font-medium text-zinc-600 shadow-sm backdrop-blur">
                    <span class="flex h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                    Restaurant OS for modern teams
                </div>

                <h1 class="landing-display text-4xl font-semibold tracking-tight text-zinc-950 sm:text-5xl lg:text-[3.5rem] lg:leading-[1.08]">
                    Run every order, kitchen ticket &amp; rupee —
                    <span class="landing-gradient-text">from one place</span>
                </h1>

                <p class="mx-auto mt-6 max-w-2xl text-base leading-relaxed text-zinc-600 sm:text-lg">
                    KotBean unifies POS, KOT, inventory, billing, loyalty, and daily close into a single polished workspace built for Indian cafés and restaurants.
                </p>

                <div class="mt-10 flex flex-col items-center justify-center gap-3 sm:flex-row">
                    @auth
                        <a href="{{ route('dashboard') }}" class="landing-btn-primary inline-flex min-w-[200px] items-center justify-center gap-2 rounded-2xl px-7 py-3.5 text-sm font-semibold text-zinc-950">
                            Open dashboard
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
                        </a>
                    @else
                        <a href="{{ route('register') }}" class="landing-btn-primary inline-flex min-w-[200px] items-center justify-center gap-2 rounded-2xl px-7 py-3.5 text-sm font-semibold text-zinc-950">
                            Start free today
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
                        </a>
                        <a href="{{ route('login') }}" class="inline-flex min-w-[200px] items-center justify-center rounded-2xl border border-zinc-200 bg-white px-7 py-3.5 text-sm font-semibold text-zinc-800 shadow-sm transition hover:border-zinc-300 hover:bg-zinc-50">
                            View live demo
                        </a>
                    @endauth
                </div>

                <p class="mt-4 text-xs text-zinc-500">No credit card · GST invoices · Works on tablet, phone &amp; desktop</p>
            </div>

            {{-- Product bento --}}
            <div class="landing-reveal mx-auto mt-16 max-w-5xl sm:mt-20">
                <div class="landing-product-shell rounded-[1.75rem] p-2 sm:p-3">
                    <div class="overflow-hidden rounded-[1.35rem] border border-zinc-200/80 bg-white shadow-2xl shadow-zinc-900/10">
                        <div class="flex items-center gap-2 border-b border-zinc-100 bg-zinc-50/80 px-4 py-3">
                            <span class="h-2.5 w-2.5 rounded-full bg-red-400"></span>
                            <span class="h-2.5 w-2.5 rounded-full bg-amber-400"></span>
                            <span class="h-2.5 w-2.5 rounded-full bg-emerald-400"></span>
                            <span class="ml-3 text-[11px] font-medium text-zinc-400">app.kotbean.com/pos</span>
                        </div>

                        <div class="grid lg:grid-cols-[220px_1fr_240px]">
                            {{-- Sidebar --}}
                            <div class="hidden border-r border-zinc-100 bg-zinc-50/50 p-4 lg:block">
                                <p class="text-[10px] font-semibold uppercase tracking-widest text-zinc-400">Workspace</p>
                                <div class="mt-4 space-y-1.5">
                                    @foreach (['Dashboard', 'POS', 'Kitchen', 'Orders', 'Inventory'] as $i => $item)
                                        <div class="flex items-center gap-2 rounded-lg px-2.5 py-2 text-xs font-medium {{ $i === 1 ? 'bg-white text-zinc-900 shadow-sm ring-1 ring-zinc-200/80' : 'text-zinc-500' }}">
                                            <span class="h-1.5 w-1.5 rounded-full {{ $i === 1 ? 'bg-amber-500' : 'bg-zinc-300' }}"></span>
                                            {{ $item }}
                                        </div>
                                    @endforeach
                                </div>
                            </div>

                            {{-- POS grid --}}
                            <div class="p-4 sm:p-5">
                                <div class="mb-4 flex items-center justify-between">
                                    <div>
                                        <p class="text-sm font-semibold text-zinc-900">Point of Sale</p>
                                        <p class="text-xs text-zinc-500">Table 4 · Dine in</p>
                                    </div>
                                    <span class="rounded-full bg-emerald-50 px-2.5 py-1 text-[10px] font-semibold text-emerald-700 ring-1 ring-emerald-100">Live</span>
                                </div>
                                <div class="grid grid-cols-2 gap-2 sm:grid-cols-3">
                                    @foreach ([['Espresso', '₹120', 'from-amber-100 to-orange-50'], ['Latte', '₹180', 'from-orange-100 to-amber-50'], ['Croissant', '₹120', 'from-yellow-100 to-amber-50'], ['Sandwich', '₹220', 'from-lime-100 to-emerald-50'], ['Cold Brew', '₹160', 'from-sky-100 to-cyan-50'], ['Muffin', '₹90', 'from-rose-100 to-pink-50']] as [$name, $price, $grad])
                                        <div class="rounded-xl border border-zinc-100 bg-white p-2.5 transition hover:border-zinc-200 hover:shadow-md">
                                            <div class="mb-2 aspect-[4/3] rounded-lg bg-gradient-to-br {{ $grad }}"></div>
                                            <p class="truncate text-xs font-semibold text-zinc-800">{{ $name }}</p>
                                            <p class="text-[11px] font-medium text-amber-700">{{ $price }}</p>
                                        </div>
                                    @endforeach
                                </div>
                            </div>

                            {{-- Order panel --}}
                            <div class="border-t border-zinc-100 bg-zinc-50/30 p-4 lg:border-l lg:border-t-0">
                                <p class="text-xs font-semibold text-zinc-900">Current order</p>
                                <p class="text-[10px] text-zinc-500">#KB-0042</p>
                                <div class="mt-4 space-y-2.5">
                                    <div class="flex justify-between text-xs"><span class="text-zinc-600">2× Latte</span><span class="font-medium text-zinc-900">₹360</span></div>
                                    <div class="flex justify-between text-xs"><span class="text-zinc-600">1× Croissant</span><span class="font-medium text-zinc-900">₹120</span></div>
                                    <div class="flex justify-between border-t border-zinc-200 pt-2 text-sm font-bold text-zinc-900"><span>Total</span><span>₹480</span></div>
                                </div>
                                <button type="button" class="landing-btn-primary mt-4 w-full rounded-xl py-2.5 text-xs font-bold text-zinc-950">Pay &amp; send KOT</button>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Floating cards --}}
                <div class="pointer-events-none relative mx-auto hidden max-w-5xl sm:block">
                    <div class="landing-float absolute -left-2 top-8 w-52 rounded-2xl border border-zinc-200/80 bg-white/90 p-4 shadow-xl backdrop-blur">
                        <div class="flex items-center gap-2">
                            <span class="flex h-8 w-8 items-center justify-center rounded-full bg-emerald-100 text-emerald-600">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            </span>
                            <div>
                                <p class="text-[10px] font-semibold uppercase tracking-wide text-emerald-600">Kitchen</p>
                                <p class="text-xs font-semibold text-zinc-900">KOT sent · Table 4</p>
                            </div>
                        </div>
                    </div>
                    <div class="landing-float-delay absolute -right-2 bottom-4 w-48 rounded-2xl border border-zinc-200/80 bg-white/90 p-4 shadow-xl backdrop-blur">
                        <p class="text-[10px] font-medium uppercase tracking-wide text-zinc-500">Today&apos;s revenue</p>
                        <p class="mt-1 text-2xl font-bold tracking-tight text-zinc-900">₹12,480</p>
                        <p class="mt-0.5 text-xs font-medium text-emerald-600">+18.2% vs yesterday</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- Logos / trust --}}
    <section class="border-y border-zinc-200/80 bg-white py-10">
        <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">
            <p class="text-center text-xs font-medium uppercase tracking-[0.2em] text-zinc-400">Trusted by growing food businesses</p>
            <div class="mt-6 flex flex-wrap items-center justify-center gap-x-10 gap-y-4 opacity-60">
                @foreach (['Bean Brew Café', 'Urban Bites', 'Chai & Co.', 'The Daily Grind', 'Spice Route'] as $brand)
                    <span class="text-sm font-semibold tracking-tight text-zinc-500">{{ $brand }}</span>
                @endforeach
            </div>
        </div>
    </section>

    {{-- Metrics --}}
    <section class="py-16 sm:py-20">
        <div class="mx-auto grid max-w-6xl grid-cols-2 gap-6 px-4 sm:px-6 md:grid-cols-4 lg:px-8">
            @foreach ([['3×', 'Faster service'], ['<2s', 'KOT delivery'], ['100%', 'Order accuracy'], ['24/7', 'Cloud uptime']] as [$val, $lbl])
                <div class="landing-reveal landing-stat rounded-2xl border border-zinc-200/80 bg-white p-6 text-center shadow-sm">
                    <p class="text-3xl font-bold tracking-tight text-zinc-900 sm:text-4xl">{{ $val }}</p>
                    <p class="mt-1 text-sm text-zinc-500">{{ $lbl }}</p>
                </div>
            @endforeach
        </div>
    </section>

    {{-- Bento features --}}
    <section id="features" class="pb-20 pt-4 sm:pb-28">
        <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">
            <div class="landing-reveal mx-auto max-w-2xl text-center">
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-amber-700">Capabilities</p>
                <h2 class="mt-3 text-3xl font-semibold tracking-tight text-zinc-950 sm:text-4xl">Everything your floor needs. Nothing it doesn&apos;t.</h2>
                <p class="mt-4 text-base leading-relaxed text-zinc-600">Replace fragmented tools with one system your staff actually enjoys using — from first order to end-of-day close.</p>
            </div>

            <div class="mt-14 grid grid-cols-1 gap-4 lg:grid-cols-6">
                @php
                    $bento = [
                        ['large' => true, 'title' => 'Lightning-fast POS', 'desc' => 'Tap-to-order with tables, variants, split bills, promos, and instant KOT firing. Designed for rush hour.', 'tone' => 'from-amber-50 via-white to-orange-50', 'icon' => 'M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 00-3 3h15.75m-12.75-3h11.218c1.121-2.3 2.1-4.684 2.924-7.138a60.114 60.114 0 00-16.536-1.84M7.5 14.25L5.106 5.272M6 20.25a.75.75 0 11-1.5 0 .75.75 0 011.5 0zm12.75 0a.75.75 0 11-1.5 0 .75.75 0 011.5 0z'],
                        ['large' => true, 'title' => 'Real-time kitchen display', 'desc' => 'Tickets appear instantly with status tracking, alerts, and one-click print. Zero missed orders.', 'tone' => 'from-emerald-50 via-white to-teal-50', 'icon' => 'M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09z'],
                        ['title' => 'Smart inventory', 'desc' => 'Auto-deduct, low-stock alerts, restock & wastage logs.', 'tone' => 'from-white to-zinc-50', 'icon' => 'M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z'],
                        ['title' => 'Loyalty & WhatsApp', 'desc' => 'Points, CRM, and automated customer receipts.', 'tone' => 'from-white to-violet-50/50', 'icon' => 'M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z'],
                        ['title' => 'Reports & cash close', 'desc' => 'Daily sales, CSV export, register reconciliation.', 'tone' => 'from-white to-sky-50/50', 'icon' => 'M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z'],
                        ['title' => 'Roles & promotions', 'desc' => 'Granular permissions and auto-apply discounts.', 'tone' => 'from-white to-rose-50/50', 'icon' => 'M9.568 3H5.25A2.25 2.25 0 003 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581c.699.699 1.78.872 2.607.33a18.095 18.095 0 005.223-5.223c.542-.827.369-1.908-.33-2.607L11.16 3.66A2.25 2.25 0 009.568 3z'],
                    ];
                @endphp
                @foreach ($bento as $card)
                    <div class="landing-reveal landing-card group {{ ! empty($card['large']) ? 'lg:col-span-3' : 'lg:col-span-2' }} rounded-3xl border border-zinc-200/80 bg-gradient-to-br {{ $card['tone'] }} p-6 shadow-sm transition hover:border-zinc-300 hover:shadow-lg sm:p-7 {{ ! empty($card['large']) ? 'lg:min-h-[220px]' : '' }}">
                        <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-white text-amber-600 shadow-sm ring-1 ring-zinc-200/80 transition group-hover:scale-105">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $card['icon'] }}"/></svg>
                        </div>
                        <h3 class="mt-5 text-lg font-semibold tracking-tight text-zinc-900">{{ $card['title'] }}</h3>
                        <p class="mt-2 text-sm leading-relaxed text-zinc-600">{{ $card['desc'] }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- Platform workflow --}}
    <section id="platform" class="bg-zinc-950 py-20 sm:py-28">
        <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">
            <div class="landing-reveal mx-auto max-w-2xl text-center">
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-amber-400/90">Workflow</p>
                <h2 class="mt-3 text-3xl font-semibold tracking-tight text-white sm:text-4xl">Three steps to a smoother service</h2>
            </div>

            <div class="mt-14 grid gap-6 md:grid-cols-3">
                @foreach ([
                    ['01', 'Configure your menu', 'Categories, variants, tax, and AI product images — ready in minutes.', 'border-amber-500/30 bg-amber-500/10 text-amber-300'],
                    ['02', 'Serve from POS', 'Staff take orders, apply promos, and collect payment without friction.', 'border-sky-500/30 bg-sky-500/10 text-sky-300'],
                    ['03', 'Kitchen & close', 'KOTs fire instantly. End the day with cash register reconciliation.', 'border-emerald-500/30 bg-emerald-500/10 text-emerald-300'],
                ] as [$num, $title, $desc, $badgeClass])
                    <div class="landing-reveal rounded-3xl border border-zinc-800 bg-zinc-900/50 p-7 backdrop-blur">
                        <span class="inline-flex rounded-full border px-3 py-1 text-xs font-semibold {{ $badgeClass }}">Step {{ $num }}</span>
                        <h3 class="mt-5 text-xl font-semibold text-white">{{ $title }}</h3>
                        <p class="mt-3 text-sm leading-relaxed text-zinc-400">{{ $desc }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- Testimonials --}}
    <section class="py-20 sm:py-28">
        <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">
            <div class="landing-reveal grid gap-6 lg:grid-cols-3">
                @foreach ([
                    ['"We cut order time dramatically. The kitchen finally runs on the same rhythm as the floor."', 'Priya S.', 'Owner, Bean Brew Café'],
                    ['"GST invoices, inventory, and WhatsApp receipts — all without switching tabs. That alone sold us."', 'Rahul M.', 'Manager, Urban Bites'],
                    ['"Closing the register used to take an hour. Now it\'s five minutes and we trust the numbers."', 'Anita K.', 'Cashier, Chai & Co.'],
                ] as [$quote, $name, $role])
                    <figure class="rounded-3xl border border-zinc-200/80 bg-white p-7 shadow-sm">
                        <div class="flex gap-0.5 text-amber-400">
                            @for ($i = 0; $i < 5; $i++)
                                <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                            @endfor
                        </div>
                        <blockquote class="mt-4 text-sm leading-relaxed text-zinc-700">{{ $quote }}</blockquote>
                        <figcaption class="mt-6 flex items-center gap-3">
                            <span class="flex h-10 w-10 items-center justify-center rounded-full bg-gradient-to-br from-amber-200 to-orange-300 text-xs font-bold text-zinc-800">{{ substr($name, 0, 1) }}</span>
                            <div>
                                <p class="text-sm font-semibold text-zinc-900">{{ $name }}</p>
                                <p class="text-xs text-zinc-500">{{ $role }}</p>
                            </div>
                        </figcaption>
                    </figure>
                @endforeach
            </div>
        </div>
    </section>

    {{-- Pricing --}}
    <section id="pricing" class="border-t border-zinc-200/80 bg-white py-20 sm:py-28">
        <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">
            <div class="landing-reveal mx-auto max-w-2xl text-center">
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-amber-700">Pricing</p>
                <h2 class="mt-3 text-3xl font-semibold tracking-tight text-zinc-950 sm:text-4xl">Simple plans. Serious value.</h2>
                <p class="mt-4 text-base text-zinc-600">Start free. Upgrade when your team and volume grow.</p>
            </div>

            <div class="landing-reveal mx-auto mt-14 grid max-w-4xl gap-6 lg:grid-cols-2">
                <div class="rounded-3xl border border-zinc-200 bg-zinc-50/50 p-8">
                    <p class="text-sm font-semibold text-zinc-900">Starter</p>
                    <p class="mt-1 text-sm text-zinc-500">For single-location cafés</p>
                    <p class="mt-6 flex items-end gap-1"><span class="text-5xl font-bold tracking-tight text-zinc-900">₹0</span><span class="mb-1.5 text-zinc-500">/mo</span></p>
                    <ul class="mt-8 space-y-3">
                        @foreach (['Unlimited orders & KOTs', 'Up to 5 staff', 'Inventory & GST invoices', 'PWA install'] as $item)
                            <li class="flex items-center gap-2.5 text-sm text-zinc-600">
                                <svg class="h-4 w-4 shrink-0 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                {{ $item }}
                            </li>
                        @endforeach
                    </ul>
                    <a href="{{ route('register') }}" class="mt-8 block rounded-2xl border border-zinc-300 bg-white py-3 text-center text-sm font-semibold text-zinc-900 transition hover:bg-zinc-50">Get started free</a>
                </div>

                <div class="relative overflow-hidden rounded-3xl border border-zinc-900 bg-zinc-950 p-8 text-white shadow-2xl">
                    <div class="pointer-events-none absolute -right-8 -top-8 h-40 w-40 rounded-full bg-amber-500/20 blur-3xl"></div>
                    <span class="inline-flex rounded-full bg-amber-400 px-3 py-1 text-[10px] font-bold uppercase tracking-wider text-zinc-950">Recommended</span>
                    <p class="mt-4 text-sm font-semibold">Pro</p>
                    <p class="mt-1 text-sm text-zinc-400">For high-volume restaurants</p>
                    <p class="mt-6 flex items-end gap-1"><span class="text-5xl font-bold tracking-tight">₹999</span><span class="mb-1.5 text-zinc-400">/mo</span></p>
                    <ul class="mt-8 space-y-3">
                        @foreach (['Everything in Starter', 'Unlimited staff & roles', 'WhatsApp & loyalty', 'Promotions & priority support'] as $item)
                            <li class="flex items-center gap-2.5 text-sm text-zinc-300">
                                <svg class="h-4 w-4 shrink-0 text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                {{ $item }}
                            </li>
                        @endforeach
                    </ul>
                    <a href="{{ route('register') }}" class="landing-btn-primary mt-8 block rounded-2xl py-3 text-center text-sm font-bold text-zinc-950">Start 14-day trial</a>
                </div>
            </div>
        </div>
    </section>

    {{-- CTA --}}
    <section class="px-4 pb-20 sm:px-6 lg:px-8">
        <div class="landing-cta landing-reveal mx-auto max-w-6xl overflow-hidden rounded-[2rem] px-8 py-16 text-center sm:px-16 sm:py-20">
            <div class="landing-cta-glow pointer-events-none absolute inset-0"></div>
            <div class="relative">
                <h2 class="text-3xl font-semibold tracking-tight text-white sm:text-4xl">Your restaurant deserves better software</h2>
                <p class="mx-auto mt-4 max-w-xl text-base text-zinc-300">Join teams who replaced chaos with clarity. Set up in minutes — no credit card required.</p>
                <div class="mt-8 flex flex-col items-center justify-center gap-3 sm:flex-row">
                    <a href="{{ route('register') }}" class="inline-flex min-w-[180px] items-center justify-center rounded-2xl bg-white px-7 py-3.5 text-sm font-semibold text-zinc-900 transition hover:bg-zinc-100">Create free account</a>
                    <a href="{{ route('login') }}" class="inline-flex min-w-[180px] items-center justify-center rounded-2xl border border-white/20 px-7 py-3.5 text-sm font-semibold text-white transition hover:bg-white/10">Sign in</a>
                </div>
            </div>
        </div>
    </section>

    {{-- Footer --}}
    <footer class="border-t border-zinc-200 bg-white pb-10 pt-14">
        <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">
            <div class="grid gap-10 md:grid-cols-[1.4fr_1fr_1fr]">
                <div>
                    <div class="flex items-center gap-2">
                        <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-zinc-900 text-[10px] font-bold text-white">KB</span>
                        <span class="font-semibold text-zinc-900">KotBean</span>
                    </div>
                    <p class="mt-4 max-w-xs text-sm leading-relaxed text-zinc-500">The modern operating system for restaurants that care about speed, accuracy, and guest experience.</p>
                </div>
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wider text-zinc-400">Product</p>
                    <ul class="mt-4 space-y-2 text-sm text-zinc-600">
                        <li><a href="#features" data-scroll class="hover:text-zinc-900">Features</a></li>
                        <li><a href="#platform" data-scroll class="hover:text-zinc-900">Workflow</a></li>
                        <li><a href="#pricing" data-scroll class="hover:text-zinc-900">Pricing</a></li>
                    </ul>
                </div>
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wider text-zinc-400">Account</p>
                    <ul class="mt-4 space-y-2 text-sm text-zinc-600">
                        <li><a href="{{ route('login') }}" class="hover:text-zinc-900">Sign in</a></li>
                        <li><a href="{{ route('register') }}" class="hover:text-zinc-900">Register</a></li>
                    </ul>
                </div>
            </div>
            <div class="mt-12 flex flex-col items-center justify-between gap-4 border-t border-zinc-100 pt-8 sm:flex-row">
                <p class="text-xs text-zinc-400">&copy; {{ date('Y') }} KotBean. All rights reserved.</p>
                <p class="text-xs text-zinc-400">Built for Indian restaurants · GST ready · PWA enabled</p>
            </div>
        </div>
    </footer>
@endsection
