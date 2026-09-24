<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="KotBean — the modern restaurant operating system. POS, kitchen tickets, inventory, billing, loyalty, and reports in one beautiful app.">
    <title>@yield('title', config('app.name').' — Restaurant OS')</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=dm-sans:400,500,600,700,800" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('head')
</head>
<body class="font-sans antialiased text-slate-900 bg-white selection:bg-amber-200 selection:text-amber-950">
    @yield('content')

    <script>
        document.getElementById('mobile-menu-toggle')?.addEventListener('click', () => {
            document.getElementById('mobile-menu')?.classList.toggle('hidden');
        });
        document.querySelectorAll('[data-scroll]').forEach((el) => {
            el.addEventListener('click', (e) => {
                const target = document.querySelector(el.getAttribute('href'));
                if (target) {
                    e.preventDefault();
                    target.scrollIntoView({ behavior: 'smooth', block: 'start' });
                    document.getElementById('mobile-menu')?.classList.add('hidden');
                }
            });
        });
    </script>
    @stack('scripts')
</body>
</html>
