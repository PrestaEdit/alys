// Retour haptique léger via l'API navigator.vibrate du WebView Android.
// Requiert android.permission.VIBRATE au manifest (déclarée dans
// packages/alys-native/nativephp.json). Silencieux si l'API n'existe pas
// ou si window.alysHapticsEnabled est explicitement false.
//
// Le flag global est renseigné depuis le layout Blade
// (voir layouts/app.blade.php) et mis à jour à la volée via l'événement
// « haptics-changed » émis par le composant Settings.

const PATTERNS = {
    light:   10,
    medium:  20,
    heavy:   35,
    success: [10, 40, 20],
    warning: [20, 40, 20],
};

function fire(kind) {
    if (window.alysHapticsEnabled === false) return;
    if (typeof navigator === 'undefined' || typeof navigator.vibrate !== 'function') return;
    const pattern = PATTERNS[kind] ?? PATTERNS.light;
    try { navigator.vibrate(pattern); } catch (_) { /* no-op */ }
}

// API globale : window.alysHaptic('light' | 'medium' | ...)
window.alysHaptic = fire;

// Magic Alpine : x-on:click="$haptic('light')"
document.addEventListener('alpine:init', () => {
    window.Alpine.magic('haptic', () => fire);
});

// Événement window déclenché côté PHP via $this->dispatch('haptic', kind: 'success')
window.addEventListener('haptic', (event) => {
    fire(event?.detail?.kind || event?.detail?.[0]?.kind || 'light');
});
