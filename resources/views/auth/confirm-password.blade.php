<x-guest-layout title="Confirm password">
    <div class="mb-8">
        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-amber-700">Secure area</p>
        <h2 class="landing-display mt-2 text-2xl font-semibold tracking-tight text-zinc-950">Confirm your password</h2>
        <p class="mt-2 text-sm text-zinc-500">Please confirm your password before continuing.</p>
    </div>

    <form method="POST" action="{{ route('password.confirm') }}" class="space-y-5">
        @csrf

        <div>
            <x-input-label for="password" :value="__('Password')" />
            <x-text-input id="password" class="mt-1.5 block w-full" type="password" name="password" required autocomplete="current-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <x-primary-button class="w-full justify-center">
            {{ __('Confirm') }}
        </x-primary-button>
    </form>
</x-guest-layout>
