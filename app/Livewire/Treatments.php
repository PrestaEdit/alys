<?php

namespace App\Livewire;

use App\Models\Treatment;
use Livewire\Component;

class Treatments extends Component
{
    public function archive(int $id): void
    {
        Treatment::findOrFail($id)->archive();
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
