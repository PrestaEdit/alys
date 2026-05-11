<?php

namespace App\Livewire;

use App\Models\Profile;
use App\Services\ExportService;
use Livewire\Component;
use Native\Mobile\Facades\SecureStorage;
use Native\Mobile\Facades\Share;

class Export extends Component
{
    public array $selectedProfiles = [];
    public array $selectedTreatments = [];
    public bool $exporting = false;
    public string $exportError = '';

    public function mount(): void
    {
        $profiles = Profile::active()
            ->with(['treatments' => fn($q) => $q->withoutGlobalScopes()])
            ->get();

        foreach ($profiles as $profile) {
            $this->selectedProfiles[] = $profile->id;
            foreach ($profile->treatments as $treatment) {
                $this->selectedTreatments[] = $profile->id . ':' . $treatment->id;
            }
        }
    }

    public function toggleProfile(int $profileId): void
    {
        $profile = Profile::active()
            ->with(['treatments' => fn($q) => $q->withoutGlobalScopes()])
            ->find($profileId);

        if (! $profile) {
            return;
        }

        $treatmentKeys = $profile->treatments
            ->map(fn($t) => $profileId . ':' . $t->id)
            ->all();

        if (in_array($profileId, $this->selectedProfiles, true)) {
            $this->selectedProfiles = array_values(array_filter(
                $this->selectedProfiles,
                fn($id) => $id !== $profileId
            ));
            $this->selectedTreatments = array_values(array_filter(
                $this->selectedTreatments,
                fn($key) => ! in_array($key, $treatmentKeys, true)
            ));
        } else {
            $this->selectedProfiles   = array_values(array_unique([...$this->selectedProfiles, $profileId]));
            $this->selectedTreatments = array_values(array_unique([...$this->selectedTreatments, ...$treatmentKeys]));
        }
    }

    public function toggleTreatment(string $key): void
    {
        if (in_array($key, $this->selectedTreatments, true)) {
            $this->selectedTreatments = array_values(array_filter(
                $this->selectedTreatments,
                fn($k) => $k !== $key
            ));
        } else {
            $this->selectedTreatments = array_values(array_unique([...$this->selectedTreatments, $key]));
        }

        // A profile is selected only if ALL its treatments are selected
        $profiles = Profile::active()
            ->with(['treatments' => fn($q) => $q->withoutGlobalScopes()])
            ->get();

        $this->selectedProfiles = [];
        foreach ($profiles as $profile) {
            $allSelected = $profile->treatments->every(
                fn($t) => in_array($profile->id . ':' . $t->id, $this->selectedTreatments, true)
            );
            if ($allSelected && $profile->treatments->isNotEmpty()) {
                $this->selectedProfiles[] = $profile->id;
            }
        }
    }

    public function export(ExportService $exportService): void
    {
        $this->exportError = '';
        $this->exporting   = true;

        try {
            $key = SecureStorage::get('device_key');

            if ($key === null) {
                $this->exportError = 'Clés non initialisées. Allez dans Réglages > Transfert de clés.';
                return;
            }

            $treatmentIds = array_map(
                fn($k) => (int) explode(':', $k)[1],
                $this->selectedTreatments
            );

            $envelope = $exportService->generateEncrypted($key, $treatmentIds);

            $filename = 'alys-traitement-' . now()->format('Y-m-d') . '.alys';
            $tempDir  = config('nativephp-internal.tempdir') ?: sys_get_temp_dir();
            $path     = rtrim($tempDir, '/') . '/' . $filename;

            if (file_put_contents($path, $envelope) === false) {
                $this->exportError = 'Impossible d\'écrire dans : ' . $path;
                return;
            }

            Share::file('Alys Traitement', 'Export chiffré du calendrier de traitement', $path);

            session()->flash('export_success', true);
            $this->redirect(route('home'), navigate: true);
        } catch (\Throwable $e) {
            $this->exportError = get_class($e) . ': ' . $e->getMessage();
        } finally {
            $this->exporting = false;
        }
    }

    public function render(): \Illuminate\View\View
    {
        $profiles = Profile::active()
            ->with(['treatments' => fn($q) => $q->withoutGlobalScopes()])
            ->get();

        return view('livewire.export', ['profiles' => $profiles])
            ->layout('layouts.app', ['title' => 'Exporter']);
    }
}
