<x-guest-layout title="Sign in">
    <div class="mb-8">
        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-amber-700">Welcome back</p>
        <h2 class="landing-display mt-2 text-2xl font-semibold tracking-tight text-zinc-950">Sign in to KotBean</h2>
        <p class="mt-2 text-sm text-zinc-500">Use your email or mobile number to continue.</p>
    </div>

    <x-auth-session-status class="mb-5 rounded-xl bg-emerald-50 px-3 py-2 text-sm font-medium text-emerald-700" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}" class="space-y-5">
        @csrf

        <div>
            <x-input-label for="login" :value="__('Email or Mobile')" />
            <x-text-input id="login" class="mt-1.5 block w-full" type="text" name="login" :value="old('login')" required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('login')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="password" :value="__('Password')" />
            <x-text-input id="password" class="mt-1.5 block w-full" type="password" name="password" required autocomplete="current-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <div class="flex items-center justify-between gap-4">
            <label for="remember_me" class="inline-flex items-center">
                <input id="remember_me" type="checkbox" class="rounded border-zinc-300 text-amber-600 shadow-sm focus:ring-amber-500" name="remember">
                <span class="ms-2 text-sm text-zinc-600">{{ __('Remember me') }}</span>
            </label>

            @if (Route::has('password.request'))
                <a class="text-sm font-medium text-zinc-600 transition hover:text-zinc-900" href="{{ route('password.request') }}">
                    {{ __('Forgot password?') }}
                </a>
            @endif
        </div>

        <x-primary-button class="w-full justify-center">
            {{ __('Sign in') }}
        </x-primary-button>
    </form>

    <p class="mt-6 text-center text-sm text-zinc-500">
        New to KotBean?
        <a href="{{ route('register') }}" class="font-semibold text-zinc-900 hover:text-amber-700">Create a free account</a>
    </p>
</x-guest-layout>
