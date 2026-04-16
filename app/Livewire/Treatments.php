<?php

namespace App\Livewire;

use Livewire\Component;

class Treatments extends Component
{
    public function render(): \Illuminate\View\View
    {
        return view('livewire.treatments')
            ->layout('layouts.app', ['title' => 'Traitements']);
    }
}
