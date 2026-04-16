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

        $start = Carbon::parse('2025-11-26');
        $end = Carbon::parse('2027-03-31');
        $totalDays = $start->diffInDays($end);
        $elapsed = $start->diffInDays($today);
        $this->progressPercent = (int) min(100, round(($elapsed / $totalDays) * 100));
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
