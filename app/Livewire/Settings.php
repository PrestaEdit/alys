<?php

namespace App\Livewire;

use App\Services\NotificationScheduler;
use Ikromjon\LocalNotifications\Facades\LocalNotifications;
use Livewire\Component;

class Settings extends Component
{
    public function enableNotifications(NotificationScheduler $scheduler): void
    {
        $check  = LocalNotifications::checkPermission();
        $status = $check['status'] ?? 'unknown';

        if ($status === 'granted') {
            $scheduler->rescheduleAll();
            $this->dispatch('toast', message: 'Autorisation déjà accordée — rappels replanifiés.');
            return;
        }

        $result  = LocalNotifications::requestPermission();
        $granted = ($result['granted'] ?? false) || (($result['status'] ?? '') === 'granted');

        if ($granted) {
            $scheduler->rescheduleAll();
            $this->dispatch('toast', message: 'Autorisation accordée — rappels activés.');
        } else {
            // Permanent denial or unsupported: direct the user to system settings
            $this->dispatch('toast', message: "Statut : {$status}. Autorisez les notifications dans les Paramètres Android.");
        }
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.settings')
            ->layout('layouts.app', ['title' => 'Paramètres']);
    }
}
