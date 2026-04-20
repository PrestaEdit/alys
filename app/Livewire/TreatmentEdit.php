<?php

namespace App\Livewire;

use App\Models\CalendarEvent;
use App\Models\PosologyHistory;
use App\Models\Setting;
use App\Models\Treatment;
use Carbon\Carbon;
use Livewire\Component;

class TreatmentEdit extends Component
{
    public Treatment $treatment;
    public ?float $newDose = null;
    public string $note = '';
    public int $frequencyWeeks = 1;

    // Properties for general info editing
    public string $editName = '';
    public string $editCommercialName = '';
    public string $editType = '';
    public string $editUnit = '';
    public string $editColor = '';
    public string $editRecurrenceStart = '';
    public bool $editIsMedicalAct = false;
    public bool $editRequiresFasting = false;

    // Widget config
    public bool $showWidget = false;
    public string $widgetIcon = '💊';

    // Modal state
    public bool $showRecalculateModal = false;
    private bool $needsRecalculate = false;

    public const WIDGET_ICONS = ['🏥', '💉', '🔬', '💊', '🧪', '🩺', '🩹', '❤️', '🫀', '🧬'];

    public const COLORS = [
        '#3b82f6', '#10b981', '#ef4444', '#8b5cf6',
        '#0ea5e9', '#f97316', '#f59e0b', '#ec4899',
    ];

    public function mount(Treatment $treatment): void
    {
        $this->treatment = $treatment;
        $this->newDose = $treatment->current_dose !== null ? (float) $treatment->current_dose : null;
        $this->frequencyWeeks = $treatment->frequency_weeks ?? 1;

        $this->editName = $treatment->name;
        $this->editCommercialName = $treatment->commercial_name ?? '';
        $this->editType = $treatment->type === 'medical_act' ? 'cyclic' : $treatment->type;
        $this->editUnit = $treatment->unit ?? '';
        $this->editColor = $treatment->color ?? '#6366f1';
        $this->editRecurrenceStart = $treatment->recurrence_start?->toDateString() ?? '';
        $this->editIsMedicalAct = (bool) $treatment->is_medical_act;
        $this->editRequiresFasting = (bool) $treatment->requires_fasting;
        $this->showWidget = (bool) $treatment->show_widget;
        $this->widgetIcon = $treatment->widget_icon ?? '💊';
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

    public function incrementFrequency(): void
    {
        $this->frequencyWeeks++;
    }

    public function decrementFrequency(): void
    {
        $this->frequencyWeeks = max(1, $this->frequencyWeeks - 1);
    }

    public function save(): void
    {
        $rules = ['frequencyWeeks' => 'required|integer|min:1'];
        if ($this->treatment->isDosageEditable()) {
            $rules['newDose'] = 'required|numeric|min:0';
        }
        $this->validate($rules);

        if ($this->treatment->isDosageEditable() && $this->newDose !== null) {
            PosologyHistory::create([
                'treatment_id' => $this->treatment->id,
                'dose' => $this->newDose,
                'note' => $this->note ?: null,
                'started_at' => today()->toDateString(),
            ]);
            $this->treatment->update(['current_dose' => $this->newDose]);
        }

        if ($this->treatment->frequency_weeks !== null) {
            $this->treatment->update(['frequency_weeks' => $this->frequencyWeeks]);
        }

        $this->treatment->refresh();
        $this->note = '';

        session()->flash('success', 'Traitement mis à jour.');
    }

    public function saveInfo(): void
    {
        $this->validate([
            'editName'  => 'required|string|max:255',
            'editType'  => 'required|in:daily,weekly,cyclic',
            'editColor' => 'required|string',
            'editUnit'  => 'nullable|string|max:50',
        ]);

        $this->treatment->update([
            'name'             => $this->editName,
            'commercial_name'  => $this->editCommercialName ?: null,
            'type'             => $this->editType,
            'is_medical_act'   => $this->editIsMedicalAct,
            'requires_fasting' => $this->editRequiresFasting,
            'color'            => $this->editColor,
            'unit'             => !$this->editIsMedicalAct ? ($this->editUnit ?: null) : null,
        ]);
        $this->treatment->refresh();
        session()->flash('success', 'Informations mises à jour.');
    }

    public function saveWidget(): void
    {
        $this->treatment->update([
            'show_widget' => $this->showWidget,
            'widget_icon' => $this->showWidget ? $this->widgetIcon : null,
        ]);
        $this->treatment->refresh();
        session()->flash('success', 'Widget mis à jour.');
    }

    public function saveRecurrence(): void
    {
        $this->validate(['frequencyWeeks' => 'required|integer|min:1']);

        $freqChanged   = $this->frequencyWeeks !== ($this->treatment->frequency_weeks ?? 1);
        $startChanged  = $this->editRecurrenceStart !== ($this->treatment->recurrence_start?->toDateString() ?? '');

        if ($freqChanged || $startChanged) {
            $this->showRecalculateModal = true;
            return;
        }

        $this->applyRecurrenceSave();
    }

    public function confirmRecalculate(): void
    {
        $this->showRecalculateModal = false;
        $this->applyRecurrenceSave(recalculate: true);
    }

    public function cancelRecalculate(): void
    {
        $this->showRecalculateModal = false;
    }

    private function applyRecurrenceSave(bool $recalculate = false): void
    {
        $this->treatment->update([
            'frequency_weeks'  => $this->frequencyWeeks,
            'recurrence_start' => $this->editRecurrenceStart ?: null,
        ]);
        $this->treatment->refresh();

        if ($recalculate) {
            $this->recalculateFutureEvents();
        }

        session()->flash('success', 'Récurrence mise à jour.');
    }

    // Keep saveProperties as alias for backwards compat with tests
    public function saveProperties(): void
    {
        $this->saveInfo();
    }

    private function recalculateFutureEvents(): void
    {
        $today = Carbon::today();

        // Delete future non-cancelled events
        $this->treatment->calendarEvents()
            ->where('scheduled_date', '>', $today->toDateString())
            ->where('is_cancelled', false)
            ->delete();

        $endDateStr = Setting::get('treatment_end');
        if (!$endDateStr || !$this->editRecurrenceStart) {
            return;
        }

        $endDate = Carbon::parse($endDateStr);
        $start = Carbon::parse($this->editRecurrenceStart);

        // Find first occurrence >= today
        $current = $start->copy();
        if ($current->lte($today)) {
            $diff = $today->diffInDays($current, false);
            $weeksAhead = (int) ceil(abs($diff) / (7 * $this->frequencyWeeks));
            $current->addWeeks($weeksAhead * $this->frequencyWeeks);
        }

        while ($current->lte($endDate)) {
            CalendarEvent::create([
                'treatment_id' => $this->treatment->id,
                'scheduled_date' => $current->toDateString(),
                'is_cancelled' => false,
            ]);
            $current->addWeeks($this->frequencyWeeks);
        }
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.treatment-edit', [
            'history' => $this->treatment->posologyHistory()->get(),
            'colors' => self::COLORS,
            'widgetIcons' => self::WIDGET_ICONS,
        ])->layout('layouts.app', ['title' => $this->treatment->name]);
    }
}
