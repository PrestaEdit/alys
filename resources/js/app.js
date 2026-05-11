import './bootstrap';
import { HSStaticMethods, HSDatepicker } from 'preline';
window.HSDatepicker = HSDatepicker;
import QRCode from 'qrcode';
window.QRCode = QRCode;

// Initialize all Preline components on first load
document.addEventListener('DOMContentLoaded', () => {
    HSStaticMethods.autoInit();
});

// Re-initialize after each Livewire DOM update (steps, dynamic content)
document.addEventListener('livewire:initialized', () => {
    Livewire.hook('commit', ({ succeed }) => {
        succeed(() => requestAnimationFrame(() => HSDatepicker.autoInit()));
    });
});

// Sync Preline datepicker value → Livewire via event delegation (event bubbles)
document.addEventListener('change.hs.datepicker', (e) => {
    const dpEl = e.target.closest('[data-livewire-model]');
    if (!dpEl) return;
    const model = dpEl.dataset.livewireModel;
    const dates = e.detail?.selectedDates;
    const value = dates?.length ? dates[0] : '';
    const componentEl = dpEl.closest('[wire\\:id]');
    if (!componentEl) return;
    const component = window.Livewire?.find(componentEl.getAttribute('wire:id'));
    if (component) component.set(model, value);
});
