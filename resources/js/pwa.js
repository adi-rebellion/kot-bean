const isNativeApp = Boolean(window.Capacitor?.isNativePlatform?.());

if (isNativeApp) {
    document.documentElement.classList.add('native-app');

    Promise.all([
        import('@capacitor/status-bar'),
        import('@capacitor/splash-screen'),
    ]).then(([{ StatusBar, Style }, { SplashScreen }]) => {
        StatusBar.setStyle({ style: Style.Dark }).catch(() => {});
        StatusBar.setBackgroundColor({ color: '#F59E0B' }).catch(() => {});
        SplashScreen.hide().catch(() => {});
    }).catch(() => {});
}

if ('serviceWorker' in navigator && !isNativeApp) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('/sw.js').catch(() => {});
    });
}

let deferredInstallPrompt = null;

window.addEventListener('beforeinstallprompt', (event) => {
    event.preventDefault();
    deferredInstallPrompt = event;
    window.dispatchEvent(new CustomEvent('kotbean:install-available'));
});

window.kotbeanInstallApp = async () => {
    if (!deferredInstallPrompt) {
        return false;
    }

    deferredInstallPrompt.prompt();
    const { outcome } = await deferredInstallPrompt.userChoice;
    deferredInstallPrompt = null;

    return outcome === 'accepted';
};

window.kotbeanIsStandalone = () =>
    isNativeApp
    || window.matchMedia('(display-mode: standalone)').matches
    || window.navigator.standalone === true;

export { isNativeApp };
