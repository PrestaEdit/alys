<?php

namespace App\Livewire;

use App\Models\Profile;
use App\Models\Setting;
use App\Services\ActiveProfile;
use Illuminate\Validation\Rule;
use Livewire\Component;

class Onboarding extends Component
{
    public int $step = 1;
    public string $patientName = '';
    public string $color = '#0ea5e9';
    public string $treatmentStart = '';
    public string $treatmentEnd = '';

    public function nextStep(): void
    {
        if ($this->step === 1) {
            $this->validate(
                ['patientName' => 'required|string|max:100'],
                [
                    'patientName.required' => 'Le prénom est requis.',
                    'patientName.max'      => 'Le prénom ne peut pas dépasser 100 caractères.',
                ]
            );
            $this->step = 2;
            return;
        }

        if ($this->step === 2) {
            $this->validate(
                ['color' => ['required', Rule::in(Profile::COLORS)]],
                [
                    'color.required' => 'Veuillez choisir une couleur.',
                    'color.in'       => 'Cette couleur n\'est pas autorisée.',
                ]
            );
            $this->step = 3;
            return;
        }

        if ($this->step === 3) {
            $this->validate(
                [
                    'treatmentStart' => 'nullable|date',
                    'treatmentEnd'   => 'nullable|date|after:treatmentStart',
                ],
                [
                    'treatmentStart.date' => 'La date de début doit être une date valide.',
                    'treatmentEnd.date'   => 'La date de fin doit être une date valide.',
                    'treatmentEnd.after'  => 'La date de fin doit être postérieure à la date de début.',
                ]
            );
            $this->step = 4;
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
        $this->createProfile();
        return redirect('/');
    }

    public function completeAndAddTreatment()
    {
        $this->createProfile();
        return redirect('/treatments/create');
    }

    private function createProfile(): void
    {
        $profile = Profile::create([
            'name'            => $this->patientName,
            'color'           => $this->color,
            'icon'            => mb_strtoupper(mb_substr($this->patientName, 0, 1)),
            'treatment_start' => $this->treatmentStart ?: null,
            'treatment_end'   => $this->treatmentEnd ?: null,
        ]);
        app(ActiveProfile::class)->set($profile->id);
        Setting::set('onboarding_completed', '1');
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.onboarding', ['colors' => Profile::COLORS])
            ->layout('layouts.app', ['title' => 'Bienvenue']);
    }
}
