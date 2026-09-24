@extends('layouts.marketing')

@section('title', 'KotBean — Run Your Restaurant Like a Pro')

@section('content')
    {{-- Nav --}}
    <header class="fixed inset-x-0 top-0 z-50 border-b border-white/10 bg-slate-950/80 backdrop-blur-xl">
        <div class="mx-auto flex h-16 max-w-7xl items-center justify-between px-4 sm:px-6 lg:px-8">
            <a href="{{ route('home') }}" class="flex items-center gap-2.5">
                <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-gradient-to-br from-amber-400 to-amber-600 text-sm font-bold text-slate-950 shadow-lg shadow-amber-500/25">KB</span>
                <span class="text-lg font-bold tracking-tight text-white">Kot<span class="text-amber-400">Bean</span></span>
            </a>

            <nav class="hidden items-center gap-8 md:flex">
                <a href="#features" data-scroll class="text-sm font-medium text-slate-300 transition hover:text-white">Features</a>
                <a href="#how-it-works" data-scroll class="text-sm font-medium text-slate-300 transition hover:text-white">How it works</a>
                <a href="#pricing" data-scroll class="text-sm font-medium text-slate-300 transition hover:text-white">Pricing</a>
            </nav>

            <div class="hidden items-center gap-3 md:flex">
                @auth
                    <a href="{{ route('dashboard') }}" class="rounded-lg px-4 py-2 text-sm font-semibold text-slate-300 transition hover:text-white">Dashboard</a>
                @else
                    <a href="{{ route('login') }}" class="rounded-lg px-4 py-2 text-sm font-semibold text-slate-300 transition hover:text-white">Log in</a>
                    <a href="{{ route('register') }}" class="rounded-lg bg-amber-500 px-4 py-2 text-sm font-semibold text-slate-950 shadow-lg shadow-amber-500/30 transition hover:bg-amber-400">Get started free</a>
                @endauth
            </div>

            <button id="mobile-menu-toggle" type="button" class="rounded-lg p-2 text-slate-300 md:hidden" aria-label="Open menu">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
            </button>
        </div>
        <div id="mobile-menu" class="hidden border-t border-white/10 bg-slate-950 px-4 py-4 md:hidden">
            <div class="flex flex-col gap-3">
                <a href="#features" data-scroll class="rounded-lg px-3 py-2 text-sm font-medium text-slate-300">Features</a>
                <a href="#how-it-works" data-scroll class="rounded-lg px-3 py-2 text-sm font-medium text-slate-300">How it works</a>
                <a href="#pricing" data-scroll class="rounded-lg px-3 py-2 text-sm font-medium text-slate-300">Pricing</a>
                @auth
                    <a href="{{ route('dashboard') }}" class="rounded-lg bg-amber-500 px-3 py-2 text-center text-sm font-semibold text-slate-950">Dashboard</a>
                @else
                    <a href="{{ route('login') }}" class="rounded-lg px-3 py-2 text-sm font-medium text-slate-300">Log in</a>
                    <a href="{{ route('register') }}" class="rounded-lg bg-amber-500 px-3 py-2 text-center text-sm font-semibold text-slate-950">Get started free</a>
                @endauth
            </div>
        </div>
    </header>

    {{-- Hero --}}
    <section class="landing-hero relative overflow-hidden bg-slate-950 pt-28 pb-20 sm:pt-32 sm:pb-28">
        <div class="landing-glow pointer-events-none absolute inset-0"></div>
        <div class="landing-grid pointer-events-none absolute inset-0 opacity-[0.07]"></div>

        <div class="relative mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="grid items-center gap-12 lg:grid-cols-2 lg:gap-16">
                <div>
                    <div class="mb-6 inline-flex items-center gap-2 rounded-full border border-amber-500/30 bg-amber-500/10 px-4 py-1.5 text-sm font-medium text-amber-300">
                        <span class="relative flex h-2 w-2">
                            <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-amber-400 opacity-75"></span>
                            <span class="relative inline-flex h-2 w-2 rounded-full bg-amber-400"></span>
                        </span>
                        Restaurant OS · POS · KOT · Inventory
                    </div>

                    <h1 class="text-4xl font-extrabold tracking-tight text-white sm:text-5xl lg:text-6xl lg:leading-[1.1]">
                        From order to kitchen to cash —
                        <span class="bg-gradient-to-r from-amber-300 via-amber-400 to-orange-400 bg-clip-text text-transparent">one beautiful flow</span>
                    </h1>

                    <p class="mt-6 max-w-xl text-lg leading-relaxed text-slate-400">
                        KotBean is the all-in-one platform for cafés and restaurants. Take orders on a blazing-fast POS, fire tickets to the kitchen instantly, track inventory, delight customers, and close your day with confidence.
                    </p>

                    <div class="mt-10 flex flex-col gap-4 sm:flex-row sm:items-center">
                        @auth
                            <a href="{{ route('dashboard') }}" class="inline-flex items-center justify-center rounded-xl bg-amber-500 px-8 py-3.5 text-base font-bold text-slate-950 shadow-xl shadow-amber-500/30 transition hover:bg-amber-400 hover:shadow-amber-400/40">
                                Go to dashboard
                                <svg class="ml-2 h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
                            </a>
                        @else
                            <a href="{{ route('register') }}" class="inline-flex items-center justify-center rounded-xl bg-amber-500 px-8 py-3.5 text-base font-bold text-slate-950 shadow-xl shadow-amber-500/30 transition hover:bg-amber-400 hover:shadow-amber-400/40">
                                Start free — no card needed
                                <svg class="ml-2 h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
                            </a>
                            <a href="{{ route('login') }}" class="inline-flex items-center justify-center rounded-xl border border-slate-700 px-8 py-3.5 text-base font-semibold text-white transition hover:border-slate-500 hover:bg-slate-900">
                                Sign in to demo
                            </a>
                        @endauth
                    </div>

                    <div class="mt-12 flex flex-wrap items-center gap-x-8 gap-y-4 text-sm text-slate-500">
                        <span class="flex items-center gap-2"><svg class="h-5 w-5 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg> GST-ready invoices</span>
                        <span class="flex items-center gap-2"><svg class="h-5 w-5 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg> Works on any device</span>
                        <span class="flex items-center gap-2"><svg class="h-5 w-5 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg> PWA &amp; Android ready</span>
                    </div>
                </div>

                {{-- Product preview mockup --}}
                <div class="relative mx-auto w-full max-w-lg lg:max-w-none">
                    <div class="landing-float absolute -right-4 top-8 z-10 hidden rounded-2xl border border-emerald-500/30 bg-slate-900/95 px-4 py-3 shadow-2xl backdrop-blur sm:block">
                        <div class="flex items-center gap-3">
                            <span class="flex h-10 w-10 items-center justify-center rounded-full bg-emerald-500/20 text-emerald-400">
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                            </span>
                            <div>
                                <p class="text-xs font-medium text-emerald-400">New KOT · Table 4</p>
                                <p class="text-sm font-semibold text-white">2× Cappuccino, 1× Croissant</p>
                            </div>
                        </div>
                    </div>

                    <div class="rounded-2xl border border-slate-700/80 bg-slate-900 p-2 shadow-2xl shadow-black/50 ring-1 ring-white/10">
                        <div class="flex items-center gap-2 border-b border-slate-800 px-4 py-3">
                            <span class="h-3 w-3 rounded-full bg-red-500/80"></span>
                            <span class="h-3 w-3 rounded-full bg-amber-500/80"></span>
                            <span class="h-3 w-3 rounded-full bg-emerald-500/80"></span>
                            <span class="ml-4 text-xs text-slate-500">KotBean POS — Bean Brew Café</span>
                        </div>
                        <div class="grid gap-2 p-3 sm:grid-cols-[1fr_140px]">
                            <div class="rounded-xl bg-slate-800/50 p-3">
                                <p class="mb-3 text-xs font-semibold uppercase tracking-wider text-slate-500">Menu</p>
                                <div class="grid grid-cols-2 gap-2 sm:grid-cols-3">
                                    @foreach ([['Espresso', '120'], ['Latte', '180'], ['Croissant', '120'], ['Sandwich', '220'], ['Cold Brew', '160'], ['Muffin', '90']] as [$item, $price])
                                        <div class="rounded-lg border border-slate-700/50 bg-slate-800 px-2 py-3 text-center">
                                            <div class="mx-auto mb-2 h-8 w-8 rounded-lg bg-gradient-to-br from-amber-500/30 to-orange-600/20"></div>
                                            <p class="text-xs font-medium text-slate-200">{{ $item }}</p>
                                            <p class="text-[10px] text-amber-400">₹{{ $price }}</p>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                            <div class="rounded-xl bg-slate-800/80 p-3">
                                <p class="mb-2 text-xs font-semibold uppercase tracking-wider text-slate-500">Order #042</p>
                                <div class="space-y-2 text-xs">
                                    <div class="flex justify-between text-slate-300"><span>2× Latte</span><span>₹360</span></div>
                                    <div class="flex justify-between text-slate-300"><span>1× Croissant</span><span>₹120</span></div>
                                    <div class="border-t border-slate-700 pt-2 flex justify-between font-bold text-white"><span>Total</span><span>₹480</span></div>
                                </div>
                                <div class="mt-3 rounded-lg bg-amber-500 py-2 text-center text-xs font-bold text-slate-950">Pay &amp; Print KOT</div>
                            </div>
                        </div>
                    </div>

                    <div class="landing-float-delay absolute -left-6 bottom-12 z-10 hidden rounded-2xl border border-slate-700 bg-slate-900/95 px-4 py-3 shadow-xl backdrop-blur sm:block">
                        <p class="text-2xl font-bold text-white">₹12,480</p>
                        <p class="text-xs text-emerald-400">↑ 18% vs yesterday</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- Stats --}}
    <section class="border-y border-slate-200 bg-white py-12">
        <div class="mx-auto grid max-w-7xl grid-cols-2 gap-8 px-4 sm:px-6 md:grid-cols-4 lg:px-8">
            @foreach ([['3×', 'Faster order flow'], ['100%', 'Kitchen sync'], ['0', 'Missed tickets'], ['24/7', 'Cloud access']] as [$stat, $label])
                <div class="text-center">
                    <p class="text-3xl font-extrabold text-slate-900 sm:text-4xl">{{ $stat }}</p>
                    <p class="mt-1 text-sm font-medium text-slate-500">{{ $label }}</p>
                </div>
            @endforeach
        </div>
    </section>

    {{-- Features --}}
    <section id="features" class="py-24 sm:py-32">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="mx-auto max-w-2xl text-center">
                <p class="text-sm font-semibold uppercase tracking-wider text-amber-600">Everything you need</p>
                <h2 class="mt-3 text-3xl font-extrabold tracking-tight text-slate-900 sm:text-4xl">Built for the floor, the kitchen, and the books</h2>
                <p class="mt-4 text-lg text-slate-600">Stop juggling spreadsheets, paper KOTs, and five different apps. KotBean brings your whole operation under one roof.</p>
            </div>

            <div class="mt-16 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                @php
                    $features = [
                        ['icon' => 'M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 00-3 3h15.75m-12.75-3h11.218c1.121-2.3 2.1-4.684 2.924-7.138a60.114 60.114 0 00-16.536-1.84M7.5 14.25L5.106 5.272M6 20.25a.75.75 0 11-1.5 0 .75.75 0 011.5 0zm12.75 0a.75.75 0 11-1.5 0 .75.75 0 011.5 0z', 'title' => 'Lightning POS', 'desc' => 'Tap-to-order interface built for speed. Tables, variants, discounts, and split payments — handled.'],
                        ['icon' => 'M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09z', 'title' => 'Real-time Kitchen Display', 'desc' => 'KOTs hit the kitchen the moment you confirm. Status updates, alerts, and print — zero lag.'],
                        ['icon' => 'M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z', 'title' => 'Smart Inventory', 'desc' => 'Auto-deduct on sale, low-stock alerts, restock & wastage tracking. Never run out mid-rush.'],
                        ['icon' => 'M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z', 'title' => 'Customers & Loyalty', 'desc' => 'CRM, order history, loyalty points, and WhatsApp receipts — keep guests coming back.'],
                        ['icon' => 'M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z', 'title' => 'Reports & Cash Register', 'desc' => 'Daily sales, CSV exports, and end-of-day cash reconciliation. Know your numbers.'],
                        ['icon' => 'M9.568 3H5.25A2.25 2.25 0 003 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581c.699.699 1.78.872 2.607.33a18.095 18.095 0 005.223-5.223c.542-.827.369-1.908-.33-2.607L11.16 3.66A2.25 2.25 0 009.568 3z', 'title' => 'Promotions & Roles', 'desc' => 'Auto-apply discounts, promo codes, and granular staff permissions for every role.'],
                    ];
                @endphp
                @foreach ($features as $feature)
                    <div class="group rounded-2xl border border-slate-200 bg-white p-6 shadow-sm transition hover:border-amber-200 hover:shadow-lg hover:shadow-amber-500/5">
                        <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-amber-50 text-amber-600 transition group-hover:bg-amber-500 group-hover:text-white">
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $feature['icon'] }}"/></svg>
                        </div>
                        <h3 class="mt-4 text-lg font-bold text-slate-900">{{ $feature['title'] }}</h3>
                        <p class="mt-2 text-sm leading-relaxed text-slate-600">{{ $feature['desc'] }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- How it works --}}
    <section id="how-it-works" class="bg-slate-50 py-24 sm:py-32">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="mx-auto max-w-2xl text-center">
                <p class="text-sm font-semibold uppercase tracking-wider text-amber-600">Simple setup</p>
                <h2 class="mt-3 text-3xl font-extrabold tracking-tight text-slate-900 sm:text-4xl">Up and running in minutes</h2>
            </div>

            <div class="mt-16 grid gap-8 md:grid-cols-3">
                @foreach ([
                    ['step' => '01', 'title' => 'Set up your menu', 'desc' => 'Add categories, products, variants, and prices. Upload AI-generated photos in one click.'],
                    ['step' => '02', 'title' => 'Take orders on POS', 'desc' => 'Waiters and cashiers tap items, assign tables, apply promos, and collect payment — fast.'],
                    ['step' => '03', 'title' => 'Kitchen fires instantly', 'desc' => 'KOTs appear on the kitchen display. Print tickets, mark ready, and serve with zero confusion.'],
                ] as $item)
                    <div class="relative rounded-2xl bg-white p-8 shadow-sm ring-1 ring-slate-200/80">
                        <span class="text-5xl font-black text-amber-100">{{ $item['step'] }}</span>
                        <h3 class="mt-4 text-xl font-bold text-slate-900">{{ $item['title'] }}</h3>
                        <p class="mt-2 text-sm leading-relaxed text-slate-600">{{ $item['desc'] }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- Testimonial / trust --}}
    <section class="py-24">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="overflow-hidden rounded-3xl bg-gradient-to-br from-slate-900 via-slate-900 to-slate-800 px-8 py-16 sm:px-16 sm:py-20">
                <div class="mx-auto max-w-3xl text-center">
                    <svg class="mx-auto h-10 w-10 text-amber-400/60" fill="currentColor" viewBox="0 0 24 24"><path d="M14.017 21v-7.391c0-5.704 3.731-9.57 8.983-10.609l.995 2.151c-2.432.917-3.995 3.638-3.995 5.849h4v10h-9.983zm-14.017 0v-7.391c0-5.704 3.748-9.57 9-10.609l.996 2.151c-2.433.917-3.996 3.638-3.996 5.849h3.983v10h-9.983z"/></svg>
                    <blockquote class="mt-6 text-xl font-medium leading-relaxed text-white sm:text-2xl">
                        "We replaced three tools with KotBean. Orders are faster, the kitchen never misses a ticket, and closing the register takes five minutes instead of an hour."
                    </blockquote>
                    <p class="mt-6 text-sm font-semibold text-amber-400">— Café owner, Bhilai</p>
                </div>
            </div>
        </div>
    </section>

    {{-- Pricing --}}
    <section id="pricing" class="bg-slate-50 py-24 sm:py-32">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="mx-auto max-w-2xl text-center">
                <p class="text-sm font-semibold uppercase tracking-wider text-amber-600">Pricing</p>
                <h2 class="mt-3 text-3xl font-extrabold tracking-tight text-slate-900 sm:text-4xl">Start free. Scale when you grow.</h2>
                <p class="mt-4 text-lg text-slate-600">No hidden fees. Full POS, kitchen, and inventory from day one.</p>
            </div>

            <div class="mx-auto mt-16 grid max-w-4xl gap-8 lg:grid-cols-2">
                <div class="rounded-2xl border border-slate-200 bg-white p-8 shadow-sm">
                    <h3 class="text-lg font-bold text-slate-900">Starter</h3>
                    <p class="mt-2 text-sm text-slate-600">Perfect for single-location cafés getting started.</p>
                    <p class="mt-6 flex items-baseline gap-1">
                        <span class="text-5xl font-extrabold tracking-tight text-slate-900">₹0</span>
                        <span class="text-slate-500">/ month</span>
                    </p>
                    <ul class="mt-8 space-y-3 text-sm text-slate-600">
                        @foreach (['Unlimited orders & KOTs', 'Up to 5 staff accounts', 'Inventory tracking', 'GST invoices', 'PWA install'] as $perk)
                            <li class="flex items-center gap-2"><svg class="h-5 w-5 shrink-0 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>{{ $perk }}</li>
                        @endforeach
                    </ul>
                    <a href="{{ route('register') }}" class="mt-8 block rounded-xl border border-slate-300 py-3 text-center text-sm font-bold text-slate-900 transition hover:bg-slate-50">Get started free</a>
                </div>

                <div class="relative rounded-2xl border-2 border-amber-500 bg-white p-8 shadow-xl shadow-amber-500/10">
                    <span class="absolute -top-3 left-1/2 -translate-x-1/2 rounded-full bg-amber-500 px-4 py-1 text-xs font-bold uppercase tracking-wider text-slate-950">Most popular</span>
                    <h3 class="text-lg font-bold text-slate-900">Pro</h3>
                    <p class="mt-2 text-sm text-slate-600">For busy restaurants that need the full stack.</p>
                    <p class="mt-6 flex items-baseline gap-1">
                        <span class="text-5xl font-extrabold tracking-tight text-slate-900">₹999</span>
                        <span class="text-slate-500">/ month</span>
                    </p>
                    <ul class="mt-8 space-y-3 text-sm text-slate-600">
                        @foreach (['Everything in Starter', 'Unlimited staff & roles', 'WhatsApp customer messaging', 'Promotions & loyalty', 'Priority support'] as $perk)
                            <li class="flex items-center gap-2"><svg class="h-5 w-5 shrink-0 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>{{ $perk }}</li>
                        @endforeach
                    </ul>
                    <a href="{{ route('register') }}" class="mt-8 block rounded-xl bg-amber-500 py-3 text-center text-sm font-bold text-slate-950 shadow-lg shadow-amber-500/30 transition hover:bg-amber-400">Start 14-day trial</a>
                </div>
            </div>
        </div>
    </section>

    {{-- Final CTA --}}
    <section class="landing-cta relative overflow-hidden py-24">
        <div class="relative mx-auto max-w-4xl px-4 text-center sm:px-6 lg:px-8">
            <h2 class="text-3xl font-extrabold tracking-tight text-white sm:text-4xl">Ready to transform your restaurant?</h2>
            <p class="mx-auto mt-4 max-w-xl text-lg text-amber-100/80">Join cafés and restaurants already running smarter with KotBean. Set up in minutes — no credit card required.</p>
            <div class="mt-10 flex flex-col items-center justify-center gap-4 sm:flex-row">
                <a href="{{ route('register') }}" class="inline-flex items-center rounded-xl bg-white px-8 py-3.5 text-base font-bold text-slate-900 shadow-xl transition hover:bg-amber-50">Create free account</a>
                <a href="{{ route('login') }}" class="inline-flex items-center rounded-xl border border-white/30 px-8 py-3.5 text-base font-semibold text-white transition hover:bg-white/10">Sign in</a>
            </div>
        </div>
    </section>

    {{-- Footer --}}
    <footer class="border-t border-slate-200 bg-white py-12">
        <div class="mx-auto flex max-w-7xl flex-col items-center justify-between gap-6 px-4 sm:flex-row sm:px-6 lg:px-8">
            <div class="flex items-center gap-2">
                <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-amber-500 text-xs font-bold text-slate-950">KB</span>
                <span class="font-bold text-slate-900">KotBean</span>
            </div>
            <p class="text-sm text-slate-500">&copy; {{ date('Y') }} KotBean. Built for restaurants that refuse to settle.</p>
            <div class="flex gap-6 text-sm font-medium text-slate-600">
                <a href="{{ route('login') }}" class="hover:text-amber-600">Log in</a>
                <a href="{{ route('register') }}" class="hover:text-amber-600">Register</a>
            </div>
        </div>
    </footer>
@endsection
