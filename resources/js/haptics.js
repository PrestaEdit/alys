// Retour haptique via l'API navigator.vibrate du WebView Android.
// Requiert android.permission.VIBRATE au manifest (déclarée dans
// packages/alys-native/nativephp.json). Silencieux si l'API n'existe pas
// ou si window.alysHapticsEnabled est explicitement false.
//
// Le flag global est renseigné depuis le layout Blade
// (voir layouts/app.blade.php) et mis à jour à la volée via l'événement
// « haptics-changed » émis par le composant Settings.

// Vocabulaire haptique — chaque nom correspond à un sens distinct.
// Utilisé uniquement aux endroits où la vibration ajoute de la valeur
// (drag & drop, jalons, actions destructives, confirmations invisibles).
const PATTERNS = {
    // Pulsations simples : durées > 25 ms pour être perçues sur les
    // moteurs Android à seuil élevé (Xiaomi / Realme / OnePlus).
    light:   25,   // confirmation discrète (fin de drag, toast)
    medium:  50,   // pickup (début de drag)
    heavy:   80,   // rare — attention forte
    // Patterns : le rythme porte le sens.
    success: [15, 60, 30],           // bref-pause-plus long — jalon positif
    warning: [40, 80, 40, 80, 40],   // triple pulsation ferme — irréversible
};

function fire(kind) {
    if (window.alysHapticsEnabled === false) return false;
    if (typeof navigator === 'undefined' || typeof navigator.vibrate !== 'function') return false;
    const pattern = PATTERNS[kind] ?? PATTERNS.light;
    try {
        return navigator.vibrate(pattern) === true;
    } catch (_) {
        return false;
    }
}

// Rapport de capacités — appelé par le bouton diagnostic dans Réglages.
window.alysHapticStatus = () => ({
    enabled: window.alysHapticsEnabled !== false,
    hasApi:  typeof navigator !== 'undefined' && typeof navigator.vibrate === 'function',
    magicRegistered: !!(window.Alpine && window.Alpine.magic && window.__alysHapticMagicOk),
});

// Test explicite — pattern long et fort (≈2 s) qu'aucun user ne pourrait rater
// s'il est vraiment émis. Renvoie ce que navigator.vibrate a retourné.
window.alysHapticTest = () => {
    if (typeof navigator === 'undefined' || typeof navigator.vibrate !== 'function') return 'no-api';
    try {
        const ok = navigator.vibrate([400, 150, 400, 150, 400]);
        return ok === true ? 'returned-true' : 'returned-false';
    } catch (e) {
        return 'threw:' + (e?.name || 'unknown');
    }
};

// API globale : window.alysHaptic('light' | 'medium' | ...)
window.alysHaptic = fire;

// Enregistre la magic Alpine — l'ordre de chargement peut varier
// (Livewire embarque Alpine et le démarre lui-même), on tente donc à la
// fois immédiatement (si Alpine est déjà chargé) ET via alpine:init.
function registerMagic() {
    if (window.Alpine && typeof window.Alpine.magic === 'function' && !window.__alysHapticMagicOk) {
        window.Alpine.magic('haptic', () => fire);
        window.__alysHapticMagicOk = true;
    }
}
registerMagic();
document.addEventListener('alpine:init', registerMagic);
document.addEventListener('alpine:initialized', registerMagic);

// Événement window déclenché côté PHP via $this->dispatch('haptic', kind: 'success').
// Ne fonctionne QUE si le dispatch est synchronement causé par un geste
// utilisateur (contrainte user-gesture des navigateurs).
window.addEventListener('haptic', (event) => {
    fire(event?.detail?.kind || event?.detail?.[0]?.kind || 'light');
});
