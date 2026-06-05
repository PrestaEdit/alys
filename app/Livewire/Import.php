<?php

namespace App\Livewire;

use App\Events\Native\FileChosen;
use App\Services\ImportPreviewService;
use App\Services\ImportService;
use Livewire\Component;
use Native\Mobile\Attributes\OnNative;
use Native\Mobile\Facades\SecureStorage;

class Import extends Component
{
    public bool $error = false;
    public string $errorMessage = '';
    public bool $picking = false;
    public bool $importing = false;
    public bool $success = false;

    public bool $previewing = false;
    public array $previewData = [];
    public array $selectedProfiles = [];
    public array $selectedTreatments = [];
    public string $exportedAt = '';

    public function mount(ImportService $importer, ImportPreviewService $preview): void
    {
        $alysData = request()->input('alys_data');
        if ($alysData === null) {
            return;
        }

        $this->importing = true;

        $content = base64_decode($alysData, true);
        if ($content === false || $content === '') {
            $this->error = true;
            $this->errorMessage = __('data.import_err_invalid_file');
            $this->importing = false;
            return;
        }

        $this->doPreview($importer, $preview, $content);
    }

    public function pickFile(): void
    {
        $this->error = false;
        $this->picking = true;

        if (function_exists('nativephp_call')) {
            nativephp_call('FilePicker.Pick', json_encode([
                'mime' => '*/*',
                'event' => FileChosen::class,
            ]));
        }
    }

    #[OnNative(FileChosen::class)]
    public function handleFileChosen(string $filename, string $content, ImportService $importer, ImportPreviewService $preview): void
    {
        $this->picking = false;

        $rawContent = base64_decode($content, true);
        if ($rawContent === false || $rawContent === '') {
            $this->error = true;
            $this->errorMessage = __('data.import_err_invalid_file');
            return;
        }

        $this->doPreview($importer, $preview, $rawContent);
    }

    private function doPreview(ImportService $importer, ImportPreviewService $preview, string $content): void
    {
        $key = SecureStorage::get('device_key');

        if ($key === null) {
            $this->error = true;
            $this->errorMessage = __('data.import_err_no_keys');
            $this->importing = false;
            return;
        }

        try {
            $data = $importer->parse($content, $key);
        } catch (\Throwable) {
            $this->error = true;
            $this->errorMessage = __('data.import_err_wrong_key');
            $this->importing = false;
            return;
        }

        session(['alys_pending' => json_encode($data)]);

        $this->exportedAt = $data['exported_at'] ?? '';
        $this->previewData = $preview->preview($data);

        // Initialize selections — all checked by default
        $this->selectedProfiles = array_column($this->previewData, 'old_id');

        $this->selectedTreatments = [];
        foreach ($this->previewData as $profile) {
            foreach ($profile['treatments'] as $treatment) {
                $this->selectedTreatments[] = $profile['old_id'] . ':' . $treatment['name'];
            }
        }

        $this->previewing = true;
        $this->importing = false;
    }

    public function toggleProfile(int $oldId): void
    {
        $profileEntry = null;
        foreach ($this->previewData as $p) {
            if ($p['old_id'] === $oldId) {
                $profileEntry = $p;
                break;
            }
        }

        if ($profileEntry === null) {
            return;
        }

        $treatmentKeys = array_map(
            fn($t) => $oldId . ':' . $t['name'],
            $profileEntry['treatments']
        );

        if (in_array($oldId, $this->selectedProfiles, true)) {
            // Uncheck profile and all its treatments
            $this->selectedProfiles = array_values(array_filter(
                $this->selectedProfiles,
                fn($id) => $id !== $oldId
            ));
            $this->selectedTreatments = array_values(array_filter(
                $this->selectedTreatments,
                fn($key) => ! in_array($key, $treatmentKeys, true)
            ));
        } else {
            // Check profile and all its treatments
            $this->selectedProfiles = array_values(array_unique([...$this->selectedProfiles, $oldId]));
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

        // Recompute selectedProfiles: a profile is selected only if ALL its treatments are selected
        $this->selectedProfiles = [];
        foreach ($this->previewData as $profile) {
            $allSelected = true;
            foreach ($profile['treatments'] as $treatment) {
                $tKey = $profile['old_id'] . ':' . $treatment['name'];
                if (! in_array($tKey, $this->selectedTreatments, true)) {
                    $allSelected = false;
                    break;
                }
            }
            if ($allSelected && count($profile['treatments']) > 0) {
                $this->selectedProfiles[] = $profile['old_id'];
            }
        }
    }

    public function confirmImport(ImportService $importer): void
    {
        $this->error = false;

        $json = session()->pull('alys_pending');
        if (! $json) {
            $this->error = true;
            $this->errorMessage = __('data.import_err_session');
            return;
        }
        try {
            $data = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            $this->error = true;
            $this->errorMessage = __('data.import_err_corrupt');
            return;
        }

        try {
            $importer->restoreFromData($data, $this->selectedTreatments);
        } catch (\Throwable) {
            $this->error = true;
            $this->errorMessage = __('data.import_err_restore');
            return;
        }

        $this->previewing = false;
        $this->previewData = [];
        $this->selectedProfiles = [];
        $this->selectedTreatments = [];
        $this->exportedAt = '';

        $this->success = true;
        $this->dispatch('import-complete');
    }

    public function cancelPreview(): void
    {
        session()->forget('alys_pending');

        $this->previewing = false;
        $this->previewData = [];
        $this->selectedProfiles = [];
        $this->selectedTreatments = [];
        $this->exportedAt = '';
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.import')
            ->layout('layouts.app', ['title' => __('data.import_title')]);
    }
}
