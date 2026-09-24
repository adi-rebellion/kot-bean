<x-guest-layout title="Create account">
    <div class="mb-8">
        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-amber-700">Get started</p>
        <h2 class="landing-display mt-2 text-2xl font-semibold tracking-tight text-zinc-950">Create your KotBean account</h2>
        <p class="mt-2 text-sm text-zinc-500">Create your business and start taking orders in minutes.</p>
    </div>

    <form method="POST" action="{{ route('register') }}" class="space-y-5">
        @csrf

        <div>
            <x-input-label for="name" :value="__('Your name')" />
            <x-text-input id="name" class="mt-1.5 block w-full" type="text" name="name" :value="old('name')" required autofocus autocomplete="name" />
            <x-input-error :messages="$errors->get('name')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="business_name" :value="__('Business name')" />
            <x-text-input id="business_name" class="mt-1.5 block w-full" type="text" name="business_name" :value="old('business_name')" required autocomplete="organization" />
            <x-input-error :messages="$errors->get('business_name')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" class="mt-1.5 block w-full" type="email" name="email" :value="old('email')" required autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="password" :value="__('Password')" />
            <x-text-input id="password" class="mt-1.5 block w-full" type="password" name="password" required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="password_confirmation" :value="__('Confirm Password')" />
            <x-text-input id="password_confirmation" class="mt-1.5 block w-full" type="password" name="password_confirmation" required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
        </div>

        <x-primary-button class="w-full justify-center">
            {{ __('Create account') }}
        </x-primary-button>
    </form>

    <p class="mt-6 text-center text-sm text-zinc-500">
        Already have an account?
        <a href="{{ route('login') }}" class="font-semibold text-zinc-900 hover:text-amber-700">Sign in</a>
    </p>
</x-guest-layout>
