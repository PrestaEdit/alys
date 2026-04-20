<?php

namespace App\Livewire;

use App\Models\CalendarEvent;
use App\Models\Setting;
use App\Models\Treatment;
use Carbon\Carbon;
use Livewire\Component;

class TreatmentCreate extends Component
{
    public string $name = '';
    public string $commercialName = '';
    public string $type = 'daily';
    public bool $isMedicalAct = false;
    public bool $requiresFasting = false;
    public string $unit = '';
    public float $currentDose = 0;
    public string $color = '#6366f1';
    public int $frequencyWeeks = 4;
    public string $recurrenceStart = '';

    public const COLORS = [
        '#3b82f6', '#10b981', '#ef4444', '#8b5cf6',
        '#0ea5e9', '#f97316', '#f59e0b', '#ec4899',
    ];

    public function save(): void
    {
        $rules = [
            'name' => 'required|string|max:255',
            'type' => 'required|in:daily,weekly,cyclic',
            'color' => 'required|string',
        ];

        if (!$this->isMedicalAct) {
            $rules['unit'] = 'nullable|string|max:50';
        }

        if ($this->type === 'cyclic') {
            $rules['frequencyWeeks'] = 'required|integer|min:1';
            $rules['recurrenceStart'] = 'nullable|date';
        }

        $this->validate($rules);

        $treatmentData = [
            'name' => $this->name,
            'commercial_name' => $this->commercialName ?: null,
            'type' => $this->type,
            'is_medical_act' => $this->isMedicalAct,
            'requires_fasting' => $this->requiresFasting,
            'color' => $this->color,
        ];

        if (!$this->isMedicalAct) {
            $treatmentData['unit'] = $this->unit ?: null;
            $treatmentData['current_dose'] = $this->currentDose;
        }

        if ($this->type === 'cyclic') {
            $treatmentData['frequency_weeks'] = $this->frequencyWeeks;
            if ($this->recurrenceStart) {
                $treatmentData['recurrence_start'] = $this->recurrenceStart;
            }
        }

        $treatment = Treatment::create($treatmentData);

        // Generate future calendar events for cyclic treatments with a recurrence start
        if ($this->type === 'cyclic' && $this->recurrenceStart) {
            $this->generateEvents($treatment);
        }

        session()->flash('success', 'Traitement créé avec succès.');

        $this->redirect(route('treatments'), navigate: false);
    }

    private function generateEvents(Treatment $treatment): void
    {
        $endDateStr = Setting::get('treatment_end');
        if (!$endDateStr) {
            return;
        }

        $endDate = Carbon::parse($endDateStr);
        $start = Carbon::parse($this->recurrenceStart);
        $today = Carbon::today();

        // Start from recurrenceStart or today, whichever is later
        $current = $start->copy();
        if ($current->lt($today)) {
            // Find next occurrence from today
            $diff = $today->diffInDays($current);
            $weeksAhead = (int) ceil($diff / (7 * $this->frequencyWeeks));
            $current->addWeeks($weeksAhead * $this->frequencyWeeks);
        }

        while ($current->lte($endDate)) {
            CalendarEvent::create([
                'treatment_id' => $treatment->id,
                'scheduled_date' => $current->toDateString(),
                'is_cancelled' => false,
            ]);
            $current->addWeeks($this->frequencyWeeks);
        }
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.treatment-create', [
            'colors' => self::COLORS,
        ])->layout('layouts.app', ['title' => 'Nouveau traitement']);
    }
}
