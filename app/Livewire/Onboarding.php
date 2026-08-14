<?php

namespace App\Livewire;

use App\Models\Profile;
use App\Models\Setting;
use App\Services\ActiveProfile;
use Carbon\Carbon;
use Illuminate\Validation\Rule;
use Livewire\Component;

class Onboarding extends Component
{
    public int $step = 1;
    public string $patientName = '';
    public string $color = '#0ea5e9';
    public string $treatmentStart = '';
    public string $treatmentEnd = '';

    public function setLocale(string $locale): void
    {
        if (! in_array($locale, ['fr', 'en'], true)) {
            return;
        }

        Setting::set('locale', $locale);

        // Applique immédiatement pour que le re-render Livewire soit dans la
        // nouvelle langue sans perdre l'étape courante (le middleware SetLocale
        // reprendra le relais à la prochaine requête).
        app()->setLocale($locale);
        Carbon::setLocale($locale);
    }

    public function nextStep(): void
    {
        if ($this->step === 1) {
            $this->validate(
                ['patientName' => 'required|string|max:100'],
                [
                    'patientName.required' => __('profiles.validation_name_required'),
                    'patientName.max'      => __('profiles.validation_name_max'),
                ]
            );
            $this->step = 2;
            return;
        }

        if ($this->step === 2) {
            $this->validate(
                ['color' => ['required', Rule::in(Profile::COLORS)]],
                [
                    'color.required' => __('profiles.validation_color_required'),
                    'color.in'       => __('profiles.validation_color_in'),
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
                    'treatmentStart.date' => __('profiles.validation_start_date'),
                    'treatmentEnd.date'   => __('profiles.validation_end_date'),
                    'treatmentEnd.after'  => __('profiles.validation_end_after'),
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
            ->layout('layouts.app', ['title' => __('onboarding.welcome')]);
    }
}
