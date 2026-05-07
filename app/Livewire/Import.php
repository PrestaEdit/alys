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

    public function import(ImportService $importer): void
    {
        $this->validate(['file' => 'required|file|max:10240']);

        $privateKey = SecureStorage::get('device_private_key');

        if ($privateKey === null) {
            $this->error = true;
            $this->errorMessage = 'Clés de chiffrement introuvables. Effectuez un transfert de clés depuis votre ancien appareil.';
            return;
        }

        try {
            $content = file_get_contents($this->file->getRealPath());
            $importer->restore($content, $privateKey);
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
