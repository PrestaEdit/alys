import './bootstrap';
import Sortable from 'sortablejs';
window.Sortable = Sortable;
import 'preline';
import { Calendar } from 'vanilla-calendar-pro';
import QRCode from 'qrcode';
window.QRCode = QRCode;
import './haptics';

// Initialize all Preline components (autoInit is NOT called automatically in the ESM bundle)
document.addEventListener('DOMContentLoaded', () => {
    window.HSStaticMethods.autoInit();
    initDatepickers();
});

// Re-initialize datepickers after each Livewire DOM update (steps, dynamic content)
document.addEventListener('livewire:initialized', () => {
    Livewire.hook('commit', ({ succeed }) => {
        succeed(() => requestAnimationFrame(() => initDatepickers()));
    });
});

function formatDateForDisplay(isoDate, format) {
    const [y, m, d] = isoDate.split('-');
    return format.replace('YYYY', y).replace('MM', m).replace('DD', d);
}

// Reposition the calendar popup above the input when it overflows the viewport.
// vanilla-calendar-pro appends the popup to document.body; we reposition via a
// delegated click listener with a short delay so the popup is visible/measured.
// This covers both the first open (childList mutation) and subsequent opens
// (popup already in DOM, toggled visible on click).
(function setupCalendarRepositioner() {
    function reposition(container) {
        // The popup element has both data-vc="calendar" and data-vc-input
        const popup = document.body.querySelector('[data-vc-input]');
        if (!popup || window.getComputedStyle(popup).display === 'none') return;
        const rect = popup.getBoundingClientRect();
        // 100 px from the bottom: clears Android 3-button nav bar in all densities
        if (rect.bottom <= window.innerHeight - 100) return;
        const inputRect = container.getBoundingClientRect();
        const newTop = window.scrollY + inputRect.top - popup.offsetHeight - 8;
        popup.style.top = Math.max(window.scrollY + 8, newTop) + 'px';
    }

    document.addEventListener('click', (e) => {
        const container = e.target.closest?.('.hs-datepicker');
        if (!container) return;
        // 60 ms is enough for vanilla-calendar-pro to show the popup
        setTimeout(() => reposition(container), 60);
    });
})();

// Direct vanilla-calendar-pro initialization, bypassing Preline's HSDatepicker
// (which requires lodash globally — not available in the Vite/ESM build).
function initDatepickers() {
    document.querySelectorAll('.hs-datepicker:not([data-dp-init])').forEach(container => {
        container.setAttribute('data-dp-init', '');

        const opts      = JSON.parse(container.getAttribute('data-hs-datepicker') || '{}');
        const model     = container.dataset.livewireModel;
        const input     = container.querySelector('input');
        const fmt       = opts.dateFormat || 'YYYY-MM-DD';

        if (opts.selectedDates?.length && input) {
            input.value = formatDateForDisplay(opts.selectedDates[0], fmt);
        }

        const cal = new Calendar(container, {
            inputMode: true,
            selectedTheme: opts.selectedTheme || 'light',
            ...(opts.selectedDates?.length ? { selectedDates: opts.selectedDates } : {}),
            onChangeToInput(self) {
                const dates = self.context.selectedDates;
                if (!dates?.length) return;
                const iso = dates[0];
                if (input) input.value = formatDateForDisplay(iso, fmt);
                if (!model) return;
                const componentEl = container.closest('[wire\\:id]');
                if (!componentEl) return;
                const component = window.Livewire?.find(componentEl.getAttribute('wire:id'));
                if (component) component.set(model, iso);
            },
        });
        cal.init();
    });
}
