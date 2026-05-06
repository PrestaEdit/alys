<?php

namespace App\Livewire;

use App\Models\Profile;
use App\Services\ActiveProfile;
use Illuminate\Validation\Rule;
use Livewire\Component;

class Profiles extends Component
{
    public ?int $editingId = null;
    public string $editName = '';
    public string $editColor = '';
    public string $editStart = '';
    public string $editEnd = '';

    public function startEdit(int $id): void
    {
        $p = Profile::findOrFail($id);
        $this->editingId = $p->id;
        $this->editName = $p->name;
        $this->editColor = $p->color;
        $this->editStart = $p->treatment_start?->format('Y-m-d') ?? '';
        $this->editEnd = $p->treatment_end?->format('Y-m-d') ?? '';
    }

    public function cancelEdit(): void
    {
        $this->reset(['editingId', 'editName', 'editColor', 'editStart', 'editEnd']);
    }

    public function saveEdit(): void
    {
        $this->validate(
            [
                'editName'  => ['required', 'string', 'max:100',
                    Rule::unique('profiles', 'name')->ignore($this->editingId)->where(fn ($q) => $q->whereNull('archived_at')),
                ],
                'editColor' => ['required', Rule::in(Profile::COLORS)],
                'editStart' => 'nullable|date',
                'editEnd'   => 'nullable|date|after:editStart',
            ],
            [
                'editName.required' => 'Le prénom est requis.',
                'editName.max'      => 'Le prénom ne peut pas dépasser 100 caractères.',
                'editName.unique'   => 'Un profil avec ce prénom existe déjà.',
                'editColor.required'=> 'Veuillez choisir une couleur.',
                'editColor.in'      => 'Cette couleur n\'est pas autorisée.',
                'editStart.date'    => 'La date de début doit être une date valide.',
                'editEnd.date'      => 'La date de fin doit être une date valide.',
                'editEnd.after'     => 'La date de fin doit être postérieure à la date de début.',
            ]
        );

        $p = Profile::findOrFail($this->editingId);
        $p->update([
            'name'            => $this->editName,
            'icon'            => mb_strtoupper(mb_substr($this->editName, 0, 1)),
            'color'           => $this->editColor,
            'treatment_start' => $this->editStart ?: null,
            'treatment_end'   => $this->editEnd ?: null,
        ]);

        $this->cancelEdit();
    }

    public function archive(int $id): void
    {
        $p = Profile::findOrFail($id);
        $p->archive();

        $service = app(ActiveProfile::class);
        if ($service->id() === $id) {
            $next = Profile::active()->first();
            if ($next) {
                $service->set($next->id);
            } else {
                $service->clear();
            }
        }
    }

    public function unarchive(int $id): void
    {
        Profile::findOrFail($id)->unarchive();
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.profiles', [
            'active'   => Profile::active()->orderBy('name')->get(),
            'archived' => Profile::archived()->orderBy('name')->get(),
            'activeId' => app(ActiveProfile::class)->id(),
            'colors'   => Profile::COLORS,
        ])->layout('layouts.app', ['title' => 'Profils']);
    }
}
