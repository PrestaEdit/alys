<?php

namespace App\Livewire;

use Livewire\Component;

class TreatmentEdit extends Component
{
    public function render(): \Illuminate\View\View
    {
        return view('livewire.treatment-edit')
            ->layout('layouts.app', ['title' => 'Modifier le traitement']);
    }
}
