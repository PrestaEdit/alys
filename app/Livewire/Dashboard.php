<?php

namespace App\Livewire;

use App\Services\CalendarService;
use Carbon\Carbon;
use Livewire\Component;

class Dashboard extends Component
{
    public array $counters = [];
    public array $todayEvents = [];
    public ?string $nextHospitalDate = null;
    public int $daysRemaining = 0;
    public int $progressPercent = 0;

    public function mount(CalendarService $service): void
    {
        $today = Carbon::today();
        $this->counters = $service->getCounters($today);
        $this->todayEvents = $service->getEventsForDay($today);
        $this->daysRemaining = $service->getDaysRemaining($today);

        $nextVisit = $service->getNextHospitalVisit($today);
        $this->nextHospitalDate = $nextVisit?->locale('fr')->isoFormat('dddd D MMMM YYYY');

        $this->progressPercent = $service->getProgressPercent($today);
    }

    public function export(): void
    {
        // Implemented in Task 9
        session()->flash('message', 'Export à venir');
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.dashboard')
            ->layout('layouts.app', ['title' => 'Accueil']);
    }
}
