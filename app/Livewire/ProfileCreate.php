<?php

namespace App\Livewire;

use App\Models\Profile;
use App\Services\ActiveProfile;
use Illuminate\Validation\Rule;
use Livewire\Component;

class ProfileCreate extends Component
{
    public string $name = '';
    public string $color = '#0ea5e9';
    public string $treatmentStart = '';
    public string $treatmentEnd = '';

    public function save()
    {
        $this->validate(
            [
                'name'           => ['required', 'string', 'max:100',
                    Rule::unique('profiles', 'name')->where(fn ($q) => $q->whereNull('archived_at')),
                ],
                'color'          => ['required', Rule::in(Profile::COLORS)],
                'treatmentStart' => 'nullable|date',
                'treatmentEnd'   => 'nullable|date|after:treatmentStart',
            ],
            [
                'name.required'       => __('profiles.validation_name_required'),
                'name.max'            => __('profiles.validation_name_max'),
                'name.unique'         => __('profiles.validation_name_unique'),
                'color.required'      => __('profiles.validation_color_required'),
                'color.in'            => __('profiles.validation_color_in'),
                'treatmentStart.date' => __('profiles.validation_start_date'),
                'treatmentEnd.date'   => __('profiles.validation_end_date'),
                'treatmentEnd.after'  => __('profiles.validation_end_after'),
            ]
        );

        $profile = Profile::create([
            'name'            => $this->name,
            'color'           => $this->color,
            'icon'            => mb_strtoupper(mb_substr($this->name, 0, 1)),
            'treatment_start' => $this->treatmentStart ?: null,
            'treatment_end'   => $this->treatmentEnd ?: null,
        ]);

        app(ActiveProfile::class)->set($profile->id);
        return redirect('/');
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.profile-create', ['colors' => Profile::COLORS])
            ->layout('layouts.app', ['title' => __('profiles.title_create')]);
    }
}
