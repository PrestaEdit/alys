<?php

namespace App\Livewire;

use Livewire\Component;

class Calendar extends Component
{
    public function render(): \Illuminate\View\View
    {
        return view('livewire.calendar')
            ->layout('layouts.app', ['title' => 'Calendrier']);
    }
}
