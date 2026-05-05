<?php

namespace App\Livewire;

use App\Models\Setting;
use Livewire\Component;

class Onboarding extends Component
{
    public int $step = 1;
    public string $patientName = '';
    public string $treatmentStart = '';
    public string $treatmentEnd = '';

    public function nextStep(): void
    {
        if ($this->step === 1) {
            $this->validate(['patientName' => 'required|string|max:100']);
            $this->step = 2;
            return;
        }

        if ($this->step === 2) {
            $this->validate([
                'treatmentStart' => 'required|date',
                'treatmentEnd'   => 'required|date|after:treatmentStart',
            ]);
            $this->step = 3;
            return;
        }
    }

    public function previousStep(): void
    {
        if ($this->step > 1) {
            $this->step--;
        }
    }

    public function complete()
    {
        $this->persist();
        return redirect('/');
    }

    public function completeAndAddTreatment()
    {
        $this->persist();
        return redirect('/treatments/create');
    }

    private function persist(): void
    {
        Setting::set('patient_name',         $this->patientName);
        Setting::set('treatment_start',      $this->treatmentStart);
        Setting::set('treatment_end',        $this->treatmentEnd);
        Setting::set('onboarding_completed', '1');
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.onboarding')
            ->layout('layouts.app', ['title' => 'Bienvenue']);
    }
}
