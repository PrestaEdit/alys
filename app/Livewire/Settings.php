<?php

namespace App\Livewire;

use App\Services\NotificationScheduler;
use Ikromjon\LocalNotifications\Facades\LocalNotifications;
use Livewire\Component;

class Settings extends Component
{
    public function enableNotifications(NotificationScheduler $scheduler): void
    {
        LocalNotifications::requestPermission();
        $scheduler->rescheduleAll();
        $this->dispatch('toast', message: 'Notifications activées et replanifiées.');
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.settings')
            ->layout('layouts.app', ['title' => 'Paramètres']);
    }
}
