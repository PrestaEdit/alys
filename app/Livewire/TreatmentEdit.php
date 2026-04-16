<?php

namespace App\Livewire;

use App\Models\PosologyHistory;
use App\Models\Treatment;
use Livewire\Component;

class TreatmentEdit extends Component
{
    public Treatment $treatment;
    public float $newDose;
    public string $note = '';

    public function mount(Treatment $treatment): void
    {
        $this->treatment = $treatment;
        $this->newDose = (float) $treatment->current_dose;
    }

    public function increment(): void
    {
        $step = $this->treatment->unit === 'ml' ? 0.1 : 1;
        $this->newDose = round($this->newDose + $step, 2);
    }

    public function decrement(): void
    {
        $step = $this->treatment->unit === 'ml' ? 0.1 : 1;
        $this->newDose = max(0, round($this->newDose - $step, 2));
    }

    public function save(): void
    {
        $this->validate(['newDose' => 'required|numeric|min:0']);

        PosologyHistory::create([
            'treatment_id' => $this->treatment->id,
            'dose' => $this->newDose,
            'note' => $this->note ?: null,
            'started_at' => today()->toDateString(),
        ]);

        $this->treatment->update(['current_dose' => $this->newDose]);
        $this->treatment->refresh();
        $this->note = '';

        session()->flash('success', 'Posologie mise à jour.');
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.treatment-edit', [
            'history' => $this->treatment->posologyHistory()->get(),
        ])->layout('layouts.app', ['title' => $this->treatment->name]);
    }
}
