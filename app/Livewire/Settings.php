<?php

namespace App\Livewire;

use App\Models\Setting;
use App\Services\NotificationScheduler;
use Ikromjon\LocalNotifications\Facades\LocalNotifications;
use Livewire\Component;

class Settings extends Component
{
    public function setLocale(string $locale): void
    {
        if (! in_array($locale, ['fr', 'en'], true)) {
            return;
        }

        Setting::set('locale', $locale);

        // Recharger pour que le middleware SetLocale ré-applique la langue.
        $this->redirect(route('settings'), navigate: false);
    }

    public function enableNotifications(NotificationScheduler $scheduler): void
    {
        $check  = LocalNotifications::checkPermission();
        $status = $check['status'] ?? 'unknown';

        if ($status === 'granted') {
            $scheduler->rescheduleAll();
            $this->requestBatteryUnrestricted();
            $this->dispatch('toast', message: __('settings.toast_already_granted'));
            return;
        }

        $result  = LocalNotifications::requestPermission();
        $granted = ($result['granted'] ?? false) || (($result['status'] ?? '') === 'granted');

        if ($granted) {
            $scheduler->rescheduleAll();
            $this->requestBatteryUnrestricted();
            $this->dispatch('toast', message: __('settings.toast_granted'));
        } else {
            $this->dispatch('toast', message: __('settings.toast_denied', ['status' => $status]));
        }
    }

    private function requestBatteryUnrestricted(): void
    {
        if (! function_exists('nativephp_call')) {
            return;
        }

        $result = nativephp_call('Battery.CheckStatus', '{}');
        if (($result['unrestricted'] ?? false) === true) {
            return; // déjà exempt
        }

        nativephp_call('Battery.RequestUnrestricted', '{}');
    }

    public function diagNotifications(): void
    {
        $hasCall = function_exists('nativephp_call') ? 'oui' : 'non';
        $hasCan  = function_exists('nativephp_can')  ? 'oui' : 'non';

        $canSchedule = function_exists('nativephp_can')
            ? (nativephp_can('LocalNotifications.Schedule') ? 'oui' : 'non')
            : 'n/a';

        $raw = function_exists('nativephp_call')
            ? nativephp_call('LocalNotifications.CheckPermission', '{}')
            : '(non disponible)';

        $this->dispatch('toast', message:
            "nativephp_call={$hasCall} | nativephp_can={$hasCan} | can.Schedule={$canSchedule} | raw=" . ($raw ?: 'vide')
        );
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.settings')
            ->layout('layouts.app', ['title' => __('nav.settings')]);
    }
}
