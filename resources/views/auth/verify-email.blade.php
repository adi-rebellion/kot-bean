<x-guest-layout title="Verify email">
    <div class="mb-8">
        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-amber-700">Almost there</p>
        <h2 class="landing-display mt-2 text-2xl font-semibold tracking-tight text-zinc-950">Verify your email</h2>
        <p class="mt-2 text-sm text-zinc-500">Click the link we just emailed you. If it has not arrived, we can send another.</p>
    </div>

    @if (session('status') == 'verification-link-sent')
        <div class="mb-5 rounded-xl bg-emerald-50 px-3 py-2 text-sm font-medium text-emerald-700">
            {{ __('A new verification link has been sent to the email address you provided during registration.') }}
        </div>
    @endif

    <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
        <form method="POST" action="{{ route('verification.send') }}" class="flex-1">
            @csrf
            <x-primary-button class="w-full justify-center">
                {{ __('Resend verification email') }}
            </x-primary-button>
        </form>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="w-full rounded-2xl px-4 py-3 text-sm font-semibold text-zinc-600 transition hover:text-zinc-900">
                {{ __('Log out') }}
            </button>
        </form>
    </div>
</x-guest-layout>
