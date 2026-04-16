<?php

namespace App\Livewire;

use App\Models\Treatment;
use Livewire\Component;

class Treatments extends Component
{
    public function render(): \Illuminate\View\View
    {
        return view('livewire.treatments', [
            'treatments' => Treatment::with('posologyHistory')->get(),
        ])->layout('layouts.app', ['title' => 'Traitements']);
    }
}
