<?php

namespace App\Livewire;

use App\Services\ActiveProfile;
use App\Services\CalendarService;
use App\Services\ExportService;
use Carbon\Carbon;
use Livewire\Component;

class Dashboard extends Component
{
    public array $counters = [];
    public array $widgets = [];
    public array $todayEvents = [];
    public ?string $nextHospitalDate = null;
    public ?int $daysRemaining = null;
    public ?int $progressPercent = null;
    public string $patientName = '';
    public string $treatmentStartLabel = '';
    public string $treatmentEndLabel = '';

    public function mount(CalendarService $service, ActiveProfile $activeProfile): void
    {
        $profile = $activeProfile->get();
        $this->patientName = $profile?->name ?? 'Alys';

        $today = Carbon::today();
        $this->counters = $service->getCounters($today);
        $this->widgets = $service->getWidgets($today);
        $this->todayEvents = $service->getEventsForDay($today);
        $this->daysRemaining = $service->getDaysRemaining($today);

        $nextVisit = $service->getNextHospitalVisit($today);
        $this->nextHospitalDate = $nextVisit?->locale('fr')->isoFormat('dddd D MMMM YYYY');

        $this->progressPercent = $service->getProgressPercent($today);

        $start = $profile?->treatment_start;
        $end   = $profile?->treatment_end;
        $this->treatmentStartLabel = $start?->locale('fr')->isoFormat('D MMM YYYY') ?? '';
        $this->treatmentEndLabel   = $end?->locale('fr')->isoFormat('D MMM YYYY') ?? '';
    }

    public function export(ExportService $exportService): void
    {
        $json = $exportService->generate();
        $filename = 'alys-traitement-' . now()->format('Y-m-d') . '.json';
        $path = storage_path('app/' . $filename);
        file_put_contents($path, $json);

        \Native\Mobile\Facades\Share::file('Alys Traitement', 'Export du calendrier de traitement d\'Alys', $path);
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.dashboard')
            ->layout('layouts.app', ['title' => 'Accueil']);
    }
}
