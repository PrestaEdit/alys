import './bootstrap';
import 'preline';
import { Calendar } from 'vanilla-calendar-pro';
import QRCode from 'qrcode';
window.QRCode = QRCode;

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

        // Reposition popup above input when it would overflow the viewport bottom.
        // The popup is appended to document.body with absolute positioning.
        if (input) {
            input.addEventListener('click', () => {
                requestAnimationFrame(() => requestAnimationFrame(() => {
                    const popup = document.querySelector('[data-vc-input]');
                    if (!popup) return;
                    const popupRect = popup.getBoundingClientRect();
                    // 80px margin: keeps the popup clear of Android's navigation bar
                    if (popupRect.bottom > window.innerHeight - 80) {
                        const inputRect = container.getBoundingClientRect();
                        const newTop = window.scrollY + inputRect.top - popup.offsetHeight - 8;
                        popup.style.top = Math.max(window.scrollY + 8, newTop) + 'px';
                    }
                }));
            });
        }
    });
}
