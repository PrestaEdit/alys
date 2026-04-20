<?php

namespace App\Livewire;

use App\Models\Setting;
use Livewire\Component;

class Settings extends Component
{
    public string $patientName = '';
    public string $treatmentStart = '';
    public string $treatmentEnd = '';

    public function mount(): void
    {
        $this->patientName    = Setting::get('patient_name', 'Alexis');
        $this->treatmentStart = Setting::get('treatment_start', '2025-11-26');
        $this->treatmentEnd   = Setting::get('treatment_end', '2027-03-31');
    }

    public function save(): void
    {
        $this->validate([
            'patientName'    => 'required|string|max:100',
            'treatmentStart' => 'required|date',
            'treatmentEnd'   => 'required|date|after:treatmentStart',
        ]);

        Setting::set('patient_name',    $this->patientName);
        Setting::set('treatment_start', $this->treatmentStart);
        Setting::set('treatment_end',   $this->treatmentEnd);

        session()->flash('success', 'Paramètres enregistrés.');
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.settings')
            ->layout('layouts.app', ['title' => 'Paramètres']);
    }
}
