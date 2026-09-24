<div
    x-data="{
        show: false,
        dismissed: localStorage.getItem('kotbean-install-dismissed') === '1',
        init() {
            if (this.dismissed || document.documentElement.classList.contains('native-app') || window.kotbeanIsStandalone?.()) {
                return;
            }
            window.addEventListener('kotbean:install-available', () => { this.show = true; });
        },
        async install() {
            const accepted = await window.kotbeanInstallApp?.();
            if (accepted) {
                this.show = false;
            }
        },
        dismiss() {
            this.show = false;
            this.dismissed = true;
            localStorage.setItem('kotbean-install-dismissed', '1');
        },
    }"
    x-show="show"
    x-cloak
    class="safe-bottom fixed inset-x-0 bottom-0 z-50 border-t border-slate-200 bg-white p-4 shadow-[0_-8px_30px_rgba(0,0,0,0.08)] dark:border-slate-800 dark:bg-slate-900 lg:hidden"
>
    <div class="mx-auto flex max-w-lg items-center gap-3">
        <img src="{{ asset('icons/icon.svg') }}" alt="" class="h-12 w-12 shrink-0 rounded-xl">
        <div class="min-w-0 flex-1">
            <p class="text-sm font-semibold text-slate-900 dark:text-white">Install KotBean</p>
            <p class="text-xs text-slate-500">Add to your home screen for a full-screen app experience.</p>
        </div>
        <div class="flex shrink-0 gap-2">
            <button @click="dismiss()" type="button" class="rounded-lg px-2 py-2 text-xs text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-800">Later</button>
            <button @click="install()" type="button" class="rounded-lg bg-brand px-3 py-2 text-xs font-bold text-white">Install</button>
        </div>
    </div>
</div>
