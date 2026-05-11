<?php

namespace App\Livewire;

use App\Events\Native\FileSaved;
use App\Models\Profile;
use App\Services\ExportService;
use Livewire\Component;
use Native\Mobile\Attributes\OnNative;
use Native\Mobile\Facades\SecureStorage;
use Native\Mobile\Facades\Share;

class Export extends Component
{
    public array $selectedProfiles = [];
    public array $selectedTreatments = [];

    /** Set once the file is generated, before the user chooses share vs. save. */
    public string $generatedPath = '';
    public string $generatedFilename = '';

    public bool $success = false;
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

        // Recompute selectedProfiles: a profile is selected only if ALL its treatments are selected.
        [$profileId] = explode(':', $key);
        $profileId = (int) $profileId;

        $profile = Profile::active()
            ->with(['treatments' => fn($q) => $q->withoutGlobalScopes()])
            ->find($profileId);

        if (! $profile) {
            return;
        }

        $allSelectedForProfile = $profile->treatments->every(
            fn($t) => in_array($profile->id . ':' . $t->id, $this->selectedTreatments, true)
        );

        if ($allSelectedForProfile && $profile->treatments->isNotEmpty()) {
            if (! in_array($profileId, $this->selectedProfiles, true)) {
                $this->selectedProfiles = array_values(array_unique([...$this->selectedProfiles, $profileId]));
            }
        } else {
            $this->selectedProfiles = array_values(array_filter(
                $this->selectedProfiles,
                fn($id) => $id !== $profileId
            ));
        }
    }

    /**
     * Generate the encrypted export file and store its path for subsequent share/save actions.
     */
    public function generate(ExportService $exportService): void
    {
        $this->exportError   = '';
        $this->generatedPath = '';
        $this->generatedFilename = '';

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
                $this->exportError = 'Impossible d\'écrire le fichier temporaire.';
                return;
            }

            $this->generatedPath     = $path;
            $this->generatedFilename = $filename;
        } catch (\Throwable $e) {
            report($e);
            $this->exportError = 'Une erreur est survenue lors de l\'export.';
        }
    }

    /**
     * Open the native share sheet with the already-generated file.
     */
    public function share(): void
    {
        if ($this->generatedPath === '') {
            return;
        }

        Share::file('Alys Traitement', 'Export chiffré du calendrier de traitement', $this->generatedPath);
        $this->success = true;
    }

    /**
     * Open the native "Save to device" document picker with the already-generated file.
     */
    public function saveToDevice(): void
    {
        if ($this->generatedPath === '') {
            return;
        }

        if (function_exists('nativephp_call')) {
            nativephp_call('FileSaver.Save', json_encode([
                'filePath' => $this->generatedPath,
                'filename' => $this->generatedFilename,
                'event'    => FileSaved::class,
            ]));
        }
    }

    #[OnNative(FileSaved::class)]
    public function handleFileSaved(bool $success, string $error = ''): void
    {
        if ($success) {
            $this->success = true;
            $this->generatedPath     = '';
            $this->generatedFilename = '';
        } else {
            if ($error !== 'cancelled') {
                $this->exportError = 'Impossible d\'enregistrer le fichier.';
            }
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
