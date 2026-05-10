<?php

namespace App\Livewire;

use App\Models\Treatment;
use Livewire\Component;
use Native\Mobile\Attributes\OnNative;
use Native\Mobile\Events\Alert\ButtonPressed;
use Native\Mobile\Facades\Dialog;

class Treatments extends Component
{
    public ?int $pendingArchiveId = null;

    public function archive(int $id): void
    {
        $this->pendingArchiveId = $id;

        Dialog::alert('Archiver le traitement', 'Ce traitement sera masqué de la liste active. Vous pourrez le désarchiver à tout moment.', ['Annuler', 'Archiver'])
            ->id('archive-treatment')
            ->event(ButtonPressed::class)
            ->show();
    }

    #[OnNative(ButtonPressed::class)]
    public function handleArchiveButton(int $index, string $label, ?string $id = null): void
    {
        if ($id === 'archive-treatment' && $label === 'Archiver' && $this->pendingArchiveId !== null) {
            Treatment::findOrFail($this->pendingArchiveId)->archive();
            $this->pendingArchiveId = null;
        }
    }

    public function unarchive(int $id): void
    {
        Treatment::findOrFail($id)->unarchive();
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.treatments', [
            'treatments' => Treatment::active()
                ->with('posologyHistory')
                ->orderByRaw("CASE WHEN name = 'Hôpital' THEN 0 ELSE 1 END")
                ->orderBy('id')
                ->get(),
            'archived' => Treatment::archived()
                ->orderBy('name')
                ->get(),
        ])->layout('layouts.app', ['title' => 'Traitements']);
    }
}
