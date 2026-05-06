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
                'name.required'       => 'Le prénom est requis.',
                'name.max'            => 'Le prénom ne peut pas dépasser 100 caractères.',
                'name.unique'         => 'Un profil avec ce prénom existe déjà.',
                'color.required'      => 'Veuillez choisir une couleur.',
                'color.in'            => 'Cette couleur n\'est pas autorisée.',
                'treatmentStart.date' => 'La date de début doit être une date valide.',
                'treatmentEnd.date'   => 'La date de fin doit être une date valide.',
                'treatmentEnd.after'  => 'La date de fin doit être postérieure à la date de début.',
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
            ->layout('layouts.app', ['title' => 'Nouveau profil']);
    }
}
