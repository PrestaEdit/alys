<?php

namespace App\Livewire;

use Livewire\Component;

class Settings extends Component
{
    public function render(): \Illuminate\View\View
    {
        return view('livewire.settings')
            ->layout('layouts.app', ['title' => 'Paramètres']);
    }
}
