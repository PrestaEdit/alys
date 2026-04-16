<?php

namespace App\Livewire;

use App\Services\CalendarService;
use App\Services\ExportService;
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

    public function export(ExportService $exportService): void
    {
        $json = $exportService->generate();
        $filename = 'alexis-traitement-' . now()->format('Y-m-d') . '.json';
        $path = storage_path('app/' . $filename);
        file_put_contents($path, $json);

        \Native\Mobile\Facades\Share::file('Alexis Traitement', 'Export du calendrier de traitement d\'Alexis', $path);
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.dashboard')
            ->layout('layouts.app', ['title' => 'Accueil']);
    }
}
