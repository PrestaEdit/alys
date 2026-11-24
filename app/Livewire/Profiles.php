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
    public string $editWeight = '';
    public string $editHeight = '';
    public ?string $editBloodGroup = null;

    public function startEdit(int $id): void
    {
        $p = Profile::findOrFail($id);
        $this->editingId = $p->id;
        $this->editName = $p->name;
        $this->editColor = $p->color;
        $this->editStart = $p->treatment_start?->format('Y-m-d') ?? '';
        $this->editEnd = $p->treatment_end?->format('Y-m-d') ?? '';
        $this->editWeight = $p->weight_kg !== null ? (string) $p->weight_kg : '';
        $this->editHeight = $p->height_cm !== null ? (string) $p->height_cm : '';
        $this->editBloodGroup = $p->blood_group;
    }

    public function cancelEdit(): void
    {
        $this->reset([
            'editingId', 'editName', 'editColor', 'editStart', 'editEnd',
            'editWeight', 'editHeight', 'editBloodGroup',
        ]);
    }

    public function toggleBloodGroup(string $group): void
    {
        $this->editBloodGroup = $this->editBloodGroup === $group ? null : $group;
    }

    public function saveEdit(): void
    {
        [$medicalRules, $medicalMessages] = Profile::medicalValidation('editWeight', 'editHeight', 'editBloodGroup');

        $this->validate(
            [
                'editName'  => ['required', 'string', 'max:100',
                    Rule::unique('profiles', 'name')->ignore($this->editingId)->where(fn ($q) => $q->whereNull('archived_at')),
                ],
                'editColor'      => ['required', Rule::in(Profile::COLORS)],
                'editStart'      => 'nullable|date',
                'editEnd'        => 'nullable|date|after:editStart',
            ] + $medicalRules,
            [
                'editName.required' => __('profiles.validation_name_required'),
                'editName.max'      => __('profiles.validation_name_max'),
                'editName.unique'   => __('profiles.validation_name_unique'),
                'editColor.required'=> __('profiles.validation_color_required'),
                'editColor.in'      => __('profiles.validation_color_in'),
                'editStart.date'    => __('profiles.validation_start_date'),
                'editEnd.date'      => __('profiles.validation_end_date'),
                'editEnd.after'     => __('profiles.validation_end_after'),
            ] + $medicalMessages
        );

        $p = Profile::findOrFail($this->editingId);
        $p->update([
            'name'            => $this->editName,
            'icon'            => mb_strtoupper(mb_substr($this->editName, 0, 1)),
            'color'           => $this->editColor,
            'treatment_start' => $this->editStart ?: null,
            'treatment_end'   => $this->editEnd ?: null,
        ]);
        $p->updateMedical(
            $this->editWeight !== '' ? (float) $this->editWeight : null,
            $this->editHeight !== '' ? (int) $this->editHeight : null,
            $this->editBloodGroup,
        );

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
            'bloodGroups' => Profile::BLOOD_GROUPS,
        ])->layout('layouts.app', ['title' => __('profiles.title')]);
    }
}
