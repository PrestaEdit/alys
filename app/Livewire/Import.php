<?php

namespace App\Livewire;

use App\Services\ImportService;
use Livewire\Component;
use Livewire\WithFileUploads;
use Native\Mobile\Facades\SecureStorage;

class Import extends Component
{
    use WithFileUploads;

    public $file = null;
    public bool $error = false;
    public string $errorMessage = '';
    public bool $autoImporting = false;
    public bool $success = false;

    public function mount(ImportService $importer): void
    {
        $alysData = request()->input('alys_data');
        if ($alysData === null) {
            return;
        }

        $this->autoImporting = true;

        $content = base64_decode($alysData, true);
        if ($content === false || $content === '') {
            $this->error = true;
            $this->errorMessage = 'Fichier reçu invalide.';
            return;
        }

        $key = SecureStorage::get('device_key');
        if ($key === null) {
            $this->error = true;
            $this->errorMessage = 'Clés de chiffrement introuvables. Effectuez un transfert de clés depuis votre ancien appareil.';
            return;
        }

        try {
            $importer->restore($content, $key);
            $this->success = true;
            $this->dispatch('import-complete');
        } catch (\Throwable) {
            $this->error = true;
            $this->errorMessage = 'Fichier invalide ou chiffré avec une autre clé.';
        }
    }

    public function import(ImportService $importer): void
    {
        $this->validate(['file' => 'required|file|max:10240']);

        $key = SecureStorage::get('device_key');

        if ($key === null) {
            $this->error = true;
            $this->errorMessage = 'Clés de chiffrement introuvables. Effectuez un transfert de clés depuis votre ancien appareil.';
            return;
        }

        try {
            $content = file_get_contents($this->file->getRealPath());
            $importer->restore($content, $key);
            $this->dispatch('import-complete');
            $this->redirectRoute('home');
        } catch (\Throwable $e) {
            $this->error = true;
            $this->errorMessage = 'Fichier invalide ou chiffré avec une autre clé.';
        }
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.import')
            ->layout('layouts.app', ['title' => 'Importer']);
    }
}
