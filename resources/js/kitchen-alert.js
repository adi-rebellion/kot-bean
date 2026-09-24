const ALERT_SRC = '/sounds/kitchen-alert.wav';

let audioUnlocked = false;
let alertAudio = null;

function getAlertAudio() {
    if (!alertAudio) {
        alertAudio = new Audio(ALERT_SRC);
        alertAudio.preload = 'auto';
    }

    return alertAudio;
}

export function unlockKitchenAudio() {
    const audio = getAlertAudio();
    audio.volume = 1;
    audio.muted = false;

    const attempt = audio.play();

    if (attempt !== undefined) {
        attempt
            .then(() => {
                audio.pause();
                audio.currentTime = 0;
                audioUnlocked = true;
                document.dispatchEvent(new CustomEvent('kitchen-audio-unlocked'));
            })
            .catch(() => {
                audioUnlocked = false;
            });
    }
}

export function playKitchenAlert(count = 1) {
    const repeats = Math.min(Math.max(Number(count) || 1, 1), 3);
    const audio = getAlertAudio();
    audio.volume = 1;
    audio.muted = false;

    let played = 0;

    const playOnce = () => {
        audio.currentTime = 0;
        const attempt = audio.play();

        if (attempt === undefined) {
            return;
        }

        attempt
            .then(() => {
                audioUnlocked = true;
                played += 1;

                if (played < repeats) {
                    setTimeout(playOnce, 900);
                }
            })
            .catch(() => {
                audioUnlocked = false;
                document.dispatchEvent(new CustomEvent('kitchen-audio-blocked'));
            });
    };

    playOnce();
}

export function isKitchenAudioUnlocked() {
    return audioUnlocked;
}

window.kotbeanUnlockKitchenAudio = unlockKitchenAudio;
window.kotbeanPlayKitchenAlert = playKitchenAlert;
window.kotbeanIsKitchenAudioUnlocked = isKitchenAudioUnlocked;

document.addEventListener(
    'click',
    () => {
        if (!audioUnlocked) {
            unlockKitchenAudio();
        }
    },
    { once: true, capture: true },
);

document.addEventListener('livewire:init', () => {
    if (typeof Livewire === 'undefined') {
        return;
    }

    Livewire.on('kitchen-new-order', (payload) => {
        const count = Array.isArray(payload) ? payload[0]?.count : payload?.count;

        playKitchenAlert(count ?? 1);
    });
});
