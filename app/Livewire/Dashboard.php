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
    public string $exportError = '';
    public bool $exportLoading = false;
    public bool $exportSuccess = false;

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
        $this->exportError = '';
        $this->exportSuccess = false;
        $this->exportLoading = true;

        try {
            $key = \Native\Mobile\Facades\SecureStorage::get('device_key');

            if ($key === null) {
                $this->exportError = 'Clés non initialisées. Allez dans Réglages > Transfert de clés.';
                return;
            }

            $envelope = $exportService->generateEncrypted($key);

            $filename = 'alys-traitement-' . now()->format('Y-m-d') . '.alys';
            $tempDir  = config('nativephp-internal.tempdir') ?: sys_get_temp_dir();
            $path     = rtrim($tempDir, '/') . '/' . $filename;
            $written  = file_put_contents($path, $envelope);

            if ($written === false) {
                $this->exportError = 'Impossible d\'écrire dans : ' . $path;
                return;
            }

            \Native\Mobile\Facades\Share::file(
                'Alys Traitement',
                'Export chiffré du calendrier de traitement',
                $path
            );

            $this->exportSuccess = true;
        } catch (\Throwable $e) {
            $this->exportError = get_class($e) . ': ' . $e->getMessage() . ' — ' . basename($e->getFile()) . ':' . $e->getLine();
        } finally {
            $this->exportLoading = false;
        }
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.dashboard')
            ->layout('layouts.app', ['title' => 'Accueil']);
    }
}
