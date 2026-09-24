<x-guest-layout title="Reset password">
    <div class="mb-8">
        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-amber-700">Account recovery</p>
        <h2 class="landing-display mt-2 text-2xl font-semibold tracking-tight text-zinc-950">Forgot your password?</h2>
        <p class="mt-2 text-sm text-zinc-500">Enter your email and we will send a reset link.</p>
    </div>

    <x-auth-session-status class="mb-5 rounded-xl bg-emerald-50 px-3 py-2 text-sm font-medium text-emerald-700" :status="session('status')" />

    <form method="POST" action="{{ route('password.email') }}" class="space-y-5">
        @csrf

        <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" class="mt-1.5 block w-full" type="email" name="email" :value="old('email')" required autofocus />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <x-primary-button class="w-full justify-center">
            {{ __('Email reset link') }}
        </x-primary-button>
    </form>

    <p class="mt-6 text-center text-sm text-zinc-500">
        <a href="{{ route('login') }}" class="font-semibold text-zinc-900 hover:text-amber-700">Back to sign in</a>
    </p>
</x-guest-layout>
