import type { CapacitorConfig } from '@capacitor/cli';

const serverUrl = process.env.CAPACITOR_SERVER_URL;

const config: CapacitorConfig = {
    appId: 'com.kotbean.app',
    appName: 'KotBean',
    webDir: 'mobile-shell',
    android: {
        allowMixedContent: true,
    },
    server: serverUrl
        ? {
            url: serverUrl,
            cleartext: serverUrl.startsWith('http://'),
        }
        : undefined,
    plugins: {
        SplashScreen: {
            launchShowDuration: 1500,
            launchAutoHide: true,
            backgroundColor: '#F59E0B',
            showSpinner: false,
        },
        StatusBar: {
            style: 'DARK',
            backgroundColor: '#F59E0B',
        },
    },
};

export default config;
