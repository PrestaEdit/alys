<?php

namespace App\Livewire;

use Livewire\Component;

class Dashboard extends Component
{
    public function render(): \Illuminate\View\View
    {
        return view('livewire.dashboard')
            ->layout('layouts.app', ['title' => 'Accueil']);
    }
}
