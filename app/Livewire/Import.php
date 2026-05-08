<?php

namespace App\Livewire;

use App\Events\Native\FileChosen;
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

    public function mount(ImportService $importer): void
    {
        $alysData = request()->input('alys_data');
        if ($alysData === null) {
            return;
        }

        $this->importing = true;

        $content = base64_decode($alysData, true);
        if ($content === false || $content === '') {
            $this->error = true;
            $this->errorMessage = 'Fichier reçu invalide.';
            return;
        }

        $this->doImport($importer, $content);
    }

    public function pickFile(): void
    {
        $this->error = false;
        $this->picking = true;

        if (function_exists('nativephp_call')) {
            nativephp_call('FilePicker.Pick', json_encode([
                'mime' => 'application/octet-stream',
                'event' => FileChosen::class,
            ]));
        }
    }

    #[OnNative(FileChosen::class)]
    public function handleFileChosen(string $filename, string $content, ImportService $importer): void
    {
        $this->picking = false;
        $this->importing = true;

        $rawContent = base64_decode($content, true);
        if ($rawContent === false || $rawContent === '') {
            $this->error = true;
            $this->errorMessage = 'Fichier reçu invalide.';
            $this->importing = false;
            return;
        }

        $this->doImport($importer, $rawContent);
    }

    private function doImport(ImportService $importer, string $content): void
    {
        $key = SecureStorage::get('device_key');

        if ($key === null) {
            $this->error = true;
            $this->errorMessage = 'Clés de chiffrement introuvables. Effectuez un transfert de clés depuis votre ancien appareil.';
            $this->importing = false;
            return;
        }

        try {
            $importer->restore($content, $key);
            $this->success = true;
            $this->importing = false;
            $this->dispatch('import-complete');
        } catch (\Throwable) {
            $this->error = true;
            $this->errorMessage = 'Fichier invalide ou chiffré avec une autre clé.';
            $this->importing = false;
        }
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.import')
            ->layout('layouts.app', ['title' => 'Importer']);
    }
}
