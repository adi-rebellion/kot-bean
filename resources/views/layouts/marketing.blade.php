<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="KotBean — the modern restaurant operating system. POS, kitchen tickets, inventory, billing, loyalty, and reports in one beautiful app.">
    <meta name="theme-color" content="#09090b">
    <title>@yield('title', config('app.name').' — Restaurant OS')</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=dm-sans:400,500,600,700&family=instrument-sans:500,600,700" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('head')
</head>
<body class="landing-page font-sans antialiased text-zinc-900 bg-zinc-50 selection:bg-amber-200/80 selection:text-amber-950">
    @yield('content')

    <script>
        (() => {
            const nav = document.getElementById('landing-nav');
            const toggle = document.getElementById('mobile-menu-toggle');
            const menu = document.getElementById('mobile-menu');

            const onScroll = () => {
                if (!nav) return;
                nav.classList.toggle('landing-nav--scrolled', window.scrollY > 24);
            };
            window.addEventListener('scroll', onScroll, { passive: true });
            onScroll();

            toggle?.addEventListener('click', () => menu?.classList.toggle('hidden'));

            document.querySelectorAll('[data-scroll]').forEach((el) => {
                el.addEventListener('click', (e) => {
                    const target = document.querySelector(el.getAttribute('href'));
                    if (!target) return;
                    e.preventDefault();
                    target.scrollIntoView({ behavior: 'smooth', block: 'start' });
                    menu?.classList.add('hidden');
                });
            });

            const observer = new IntersectionObserver(
                (entries) => entries.forEach((entry) => {
                    if (entry.isIntersecting) entry.target.classList.add('landing-reveal--visible');
                }),
                { threshold: 0.12, rootMargin: '0px 0px -40px 0px' }
            );
            document.querySelectorAll('.landing-reveal').forEach((el) => observer.observe(el));
        })();
    </script>
    @stack('scripts')
</body>
</html>
