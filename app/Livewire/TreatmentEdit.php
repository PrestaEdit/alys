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
    public string $note = '';
    public int $frequencyWeeks = 1;

    // Posology mode: single | dayparts | interval
    public string $dosageMode = 'single';

    // Single / interval dose
    public ?float $newDose = null;
    public int $newTimesPerDay = 4;

    // Day-part posology
    public ?float $newDoseMorning = null;
    public ?float $newDoseNoon    = null;
    public ?float $newDoseEvening = null;

    // Properties for general info editing
    public string $editName = '';
    public string $editCommercialName = '';
    public string $editType = '';
    public string $editUnit = '';
    public string $editColor = '';
    public string $editRecurrenceStart = '';
    public bool $editIsMedicalAct = false;
    public bool $editRequiresFasting = false;

    // Linked treatment
    public ?int $editParentTreatmentId = null;
    public int $editLinkedDays = 1;

    // Weekly scheduling
    public int $editDayOfWeek = 0;

    // Widget config
    public bool $showWidget = false;
    public string $widgetIcon = '💊';

    // Modal state
    public bool $showRecalculateModal = false;

    // Notifications
    public bool $notificationEnabled = false;
    public string $notificationTimeMorning = '08:00';
    public string $notificationTimeNoon = '12:30';
    public string $notificationTimeEvening = '20:00';

    public const WIDGET_ICONS = ['🏥', '💉', '🔬', '💊', '🧪', '🩺', '🩹', '❤️', '🫀', '🧬'];

    public const COLORS = [
        '#3b82f6', '#10b981', '#ef4444', '#8b5cf6',
        '#0ea5e9', '#f97316', '#f59e0b', '#ec4899',
    ];

    public function mount(Treatment $treatment): void
    {
        $this->treatment = $treatment;
        $this->frequencyWeeks = $treatment->frequency_weeks ?? 1;

        // Posology
        if ($treatment->hasDayPartDoses()) {
            $this->dosageMode = 'dayparts';
        } elseif ($treatment->hasIntervalDose()) {
            $this->dosageMode = 'interval';
        } else {
            $this->dosageMode = 'single';
        }

        $this->newDose        = $treatment->current_dose !== null ? (float) $treatment->current_dose : null;
        $this->newTimesPerDay = $treatment->times_per_day ?? 4;
        // In dayparts mode, always expose all three fields (null → 0 so the row is interactive)
        $this->newDoseMorning = $treatment->dose_morning !== null ? (float) $treatment->dose_morning : ($this->dosageMode === 'dayparts' ? 0 : null);
        $this->newDoseNoon    = $treatment->dose_noon    !== null ? (float) $treatment->dose_noon    : ($this->dosageMode === 'dayparts' ? 0 : null);
        $this->newDoseEvening = $treatment->dose_evening !== null ? (float) $treatment->dose_evening : ($this->dosageMode === 'dayparts' ? 0 : null);

        // Info
        $this->editName = $treatment->name;
        $this->editCommercialName = $treatment->commercial_name ?? '';
        $this->editType = $treatment->type === 'medical_act' ? 'cyclic' : $treatment->type;
        $this->editUnit = $treatment->unit ?? '';
        $this->editColor = $treatment->color ?? '#6366f1';
        $this->editRecurrenceStart = $treatment->recurrence_start?->toDateString() ?? '';
        $this->editIsMedicalAct = (bool) $treatment->is_medical_act;
        $this->editRequiresFasting = (bool) $treatment->requires_fasting;

        // Weekly scheduling
        $this->editDayOfWeek = $treatment->day_of_week ?? 0;

        // Linked treatment
        $this->editParentTreatmentId = $treatment->parent_treatment_id;
        $this->editLinkedDays = $treatment->linked_days ?? 1;

        // Widget
        $this->showWidget = (bool) $treatment->show_widget;
        $this->widgetIcon = $treatment->widget_icon ?? '💊';

        // Notifications
        $this->notificationEnabled     = (bool) $treatment->notification_enabled;
        $this->notificationTimeMorning = $treatment->notification_time_morning ?? '08:00';
        $this->notificationTimeNoon    = $treatment->notification_time_noon    ?? '12:30';
        $this->notificationTimeEvening = $treatment->notification_time_evening ?? '20:00';
    }

    // ── Dosage mode switch ─────────────────────────────────────────────

    public function updatedDosageMode(string $value): void
    {
        if ($value === 'dayparts') {
            $this->newDoseMorning ??= 0;
            $this->newDoseNoon    ??= 0;
            $this->newDoseEvening ??= 0;
        } else {
            $this->newDose ??= 0;
        }
    }

    // ── Single-dose increments ──────────────────────────────────────────

    public function increment(): void
    {
        $step = $this->treatment->unit === 'ml' ? 0.1 : 0.5;
        $this->newDose = round(($this->newDose ?? 0) + $step, 2);
    }

    public function decrement(): void
    {
        $step = $this->treatment->unit === 'ml' ? 0.1 : 0.5;
        $this->newDose = max(0, round(($this->newDose ?? 0) - $step, 2));
    }

    // ── Times per day ───────────────────────────────────────────────────

    public function incrementTimesPerDay(): void
    {
        $this->newTimesPerDay = min(24, $this->newTimesPerDay + 1);
    }

    public function decrementTimesPerDay(): void
    {
        $this->newTimesPerDay = max(2, $this->newTimesPerDay - 1);
    }

    // ── Day-part increments ─────────────────────────────────────────────

    public function incrementMorning(): void
    {
        $step = $this->treatment->unit === 'ml' ? 0.1 : 0.5;
        $this->newDoseMorning = round(($this->newDoseMorning ?? 0) + $step, 2);
    }

    public function decrementMorning(): void
    {
        $step = $this->treatment->unit === 'ml' ? 0.1 : 0.5;
        $this->newDoseMorning = max(0, round(($this->newDoseMorning ?? 0) - $step, 2));
    }

    public function incrementNoon(): void
    {
        $step = $this->treatment->unit === 'ml' ? 0.1 : 0.5;
        $this->newDoseNoon = round(($this->newDoseNoon ?? 0) + $step, 2);
    }

    public function decrementNoon(): void
    {
        $step = $this->treatment->unit === 'ml' ? 0.1 : 0.5;
        $this->newDoseNoon = max(0, round(($this->newDoseNoon ?? 0) - $step, 2));
    }

    public function incrementEvening(): void
    {
        $step = $this->treatment->unit === 'ml' ? 0.1 : 0.5;
        $this->newDoseEvening = round(($this->newDoseEvening ?? 0) + $step, 2);
    }

    public function decrementEvening(): void
    {
        $step = $this->treatment->unit === 'ml' ? 0.1 : 0.5;
        $this->newDoseEvening = max(0, round(($this->newDoseEvening ?? 0) - $step, 2));
    }

    // ── Frequency ──────────────────────────────────────────────────────

    public function incrementFrequency(): void { $this->frequencyWeeks++; }

    public function decrementFrequency(): void
    {
        $this->frequencyWeeks = max(1, $this->frequencyWeeks - 1);
    }

    public function incrementLinkedDays(): void { $this->editLinkedDays++; }

    public function decrementLinkedDays(): void
    {
        $this->editLinkedDays = max(1, $this->editLinkedDays - 1);
    }

    // ── Save posology ──────────────────────────────────────────────────

    public function save(): void
    {
        if ($this->treatment->isDosageEditable()) {
            if ($this->dosageMode === 'dayparts') {
                PosologyHistory::create([
                    'treatment_id' => $this->treatment->id,
                    'dose_morning' => $this->newDoseMorning,
                    'dose_noon'    => $this->newDoseNoon,
                    'dose_evening' => $this->newDoseEvening,
                    'note'         => $this->note ?: null,
                    'started_at'   => today()->toDateString(),
                ]);
                $this->treatment->update([
                    'current_dose'  => null,
                    'times_per_day' => null,
                    'dose_morning'  => $this->newDoseMorning,
                    'dose_noon'     => $this->newDoseNoon,
                    'dose_evening'  => $this->newDoseEvening,
                ]);
            } elseif ($this->dosageMode === 'interval') {
                $this->validate(['newDose' => 'required|numeric|min:0']);
                PosologyHistory::create([
                    'treatment_id'  => $this->treatment->id,
                    'dose'          => $this->newDose,
                    'times_per_day' => $this->newTimesPerDay,
                    'note'          => $this->note ?: null,
                    'started_at'    => today()->toDateString(),
                ]);
                $this->treatment->update([
                    'current_dose'  => $this->newDose,
                    'times_per_day' => $this->newTimesPerDay,
                    'dose_morning'  => null,
                    'dose_noon'     => null,
                    'dose_evening'  => null,
                ]);
            } else {
                $this->validate(['newDose' => 'required|numeric|min:0']);
                PosologyHistory::create([
                    'treatment_id' => $this->treatment->id,
                    'dose'         => $this->newDose,
                    'note'         => $this->note ?: null,
                    'started_at'   => today()->toDateString(),
                ]);
                $this->treatment->update([
                    'current_dose'  => $this->newDose,
                    'times_per_day' => null,
                    'dose_morning'  => null,
                    'dose_noon'     => null,
                    'dose_evening'  => null,
                ]);
            }
        }

        $this->treatment->refresh();
        $this->note = '';
        $this->dispatch('toast', message: 'Posologie mise à jour.');
    }

    // ── Save info ──────────────────────────────────────────────────────

    public function saveInfo(): void
    {
        $this->validate([
            'editName'  => 'required|string|max:255',
            'editType'  => 'required|in:daily,weekly,cyclic',
            'editColor' => 'required|string',
            'editUnit'  => 'nullable|string|max:50',
            'editParentTreatmentId' => 'nullable|integer|exists:treatments,id',
            'editLinkedDays' => 'required|integer|min:1',
        ]);

        $prevParentId    = $this->treatment->parent_treatment_id;
        $prevLinkedDays  = $this->treatment->linked_days ?? 1;
        $parentChanged   = $this->editParentTreatmentId !== $prevParentId;
        $daysChanged     = $this->editLinkedDays !== $prevLinkedDays;

        $this->treatment->update([
            'name'                 => $this->editName,
            'commercial_name'      => $this->editCommercialName ?: null,
            'type'                 => $this->editType,
            'is_medical_act'       => $this->editIsMedicalAct,
            'requires_fasting'     => $this->editRequiresFasting,
            'color'                => $this->editColor,
            'unit'                 => !$this->editIsMedicalAct ? ($this->editUnit ?: null) : null,
            'current_dose'         => !$this->editIsMedicalAct ? $this->treatment->current_dose : null,
            'times_per_day'        => !$this->editIsMedicalAct ? $this->treatment->times_per_day : null,
            'dose_morning'         => !$this->editIsMedicalAct ? $this->treatment->dose_morning : null,
            'dose_noon'            => !$this->editIsMedicalAct ? $this->treatment->dose_noon : null,
            'dose_evening'         => !$this->editIsMedicalAct ? $this->treatment->dose_evening : null,
            'parent_treatment_id'  => $this->editParentTreatmentId,
            'linked_days'          => $this->editParentTreatmentId ? $this->editLinkedDays : null,
        ]);

        // Regenerate linked events if parent or duration changed
        if ($parentChanged || $daysChanged) {
            $this->regenerateLinkedEvents();
        }

        $this->treatment->refresh();
        $this->dispatch('toast', message: 'Informations mises à jour.');
    }

    private function regenerateLinkedEvents(): void
    {
        $today = Carbon::today()->toDateString();

        // Delete future linked events
        $this->treatment->calendarEvents()
            ->whereNotNull('parent_event_id')
            ->where('scheduled_date', '>', $today)
            ->where('is_cancelled', false)
            ->delete();

        if (!$this->editParentTreatmentId) return;

        $parentEvents = CalendarEvent::where('treatment_id', $this->editParentTreatmentId)
            ->where('scheduled_date', '>', $today)
            ->where('is_cancelled', false)
            ->get();

        foreach ($parentEvents as $parentEvent) {
            $base = Carbon::parse($parentEvent->scheduled_date);
            for ($day = 0; $day < $this->editLinkedDays; $day++) {
                CalendarEvent::create([
                    'treatment_id'    => $this->treatment->id,
                    'scheduled_date'  => $base->copy()->addDays($day)->toDateString(),
                    'parent_event_id' => $parentEvent->id,
                    'is_cancelled'    => false,
                ]);
            }
        }
    }

    // ── Save notifications ─────────────────────────────────────────────

    public function saveNotification(): void
    {
        if ($this->notificationEnabled) {
            $isDayparts = !$this->treatment->is_medical_act && $this->dosageMode === 'dayparts';

            if ($isDayparts) {
                if (!$this->notificationTimeMorning && !$this->notificationTimeNoon && !$this->notificationTimeEvening) {
                    $this->addError('notificationTimeMorning', 'Au moins une heure est requise.');
                    return;
                }
            } else {
                $this->validate(
                    ['notificationTimeMorning' => 'required|date_format:H:i'],
                    ['notificationTimeMorning.required'    => "L'heure de notification est obligatoire.",
                     'notificationTimeMorning.date_format' => "Format invalide (HH:MM)."]
                );
            }
        }

        $this->treatment->update([
            'notification_enabled'      => $this->notificationEnabled,
            'notification_time_morning' => $this->notificationEnabled ? ($this->notificationTimeMorning ?: null) : null,
            'notification_time_noon'    => $this->notificationEnabled ? ($this->notificationTimeNoon ?: null) : null,
            'notification_time_evening' => $this->notificationEnabled ? ($this->notificationTimeEvening ?: null) : null,
        ]);

        $this->treatment->refresh();
        app(\App\Services\NotificationScheduler::class)->scheduleForTreatment($this->treatment);
        $this->dispatch('toast', message: 'Notifications mises à jour.');
    }

    // ── Save widget ────────────────────────────────────────────────────

    public function saveWidget(): void
    {
        $this->treatment->update([
            'show_widget' => $this->showWidget,
            'widget_icon' => $this->showWidget ? $this->widgetIcon : null,
        ]);
        $this->treatment->refresh();
        $this->dispatch('toast', message: 'Widget mis à jour.');
    }

    // ── Save recurrence ────────────────────────────────────────────────

    public function saveRecurrence(): void
    {
        $this->validate(['frequencyWeeks' => 'required|integer|min:1']);

        $freqChanged  = $this->frequencyWeeks !== ($this->treatment->frequency_weeks ?? 1);
        $startChanged = $this->editRecurrenceStart !== ($this->treatment->recurrence_start?->toDateString() ?? '');
        $dayChanged   = $this->treatment->type === 'weekly' && $this->editDayOfWeek !== ($this->treatment->day_of_week ?? 0);

        if ($freqChanged || $startChanged || $dayChanged) {
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
        $data = [
            'frequency_weeks'  => $this->frequencyWeeks,
            'recurrence_start' => $this->editRecurrenceStart ?: null,
        ];
        if ($this->treatment->type === 'weekly') {
            $data['day_of_week'] = $this->editDayOfWeek;
        }
        $this->treatment->update($data);
        $this->treatment->refresh();

        if ($recalculate) {
            $this->recalculateFutureEvents();
        }

        $this->dispatch('toast', message: 'Récurrence mise à jour.');
    }

    private function recalculateFutureEvents(): void
    {
        $today = Carbon::today();

        $this->treatment->calendarEvents()
            ->where('scheduled_date', '>', $today->toDateString())
            ->where('is_cancelled', false)
            ->delete();

        $profile = app(\App\Services\ActiveProfile::class)->get();
        if (! $profile) return;

        $endDate = $profile->treatment_end;

        if ($this->treatment->type === 'weekly') {
            $start   = $this->editRecurrenceStart ? Carbon::parse($this->editRecurrenceStart) : $today;
            $current = $start->copy()->startOfWeek(Carbon::MONDAY)->addDays($this->editDayOfWeek);
            if ($current->lte($today)) {
                $current->addWeeks($this->frequencyWeeks);
            }
        } else {
            if (! $this->editRecurrenceStart) return;
            $start   = Carbon::parse($this->editRecurrenceStart);
            $current = $start->copy();
            if ($current->lte($today)) {
                $diff       = $today->diffInDays($current, false);
                $weeksAhead = (int) ceil(abs($diff) / (7 * $this->frequencyWeeks));
                $current->addWeeks($weeksAhead * $this->frequencyWeeks);
            }
        }

        while ($current->lte($endDate)) {
            CalendarEvent::create([
                'treatment_id'   => $this->treatment->id,
                'scheduled_date' => $current->toDateString(),
                'is_cancelled'   => false,
            ]);
            $current->addWeeks($this->frequencyWeeks);
        }
    }

    // Backward compat alias
    public function saveProperties(): void { $this->saveInfo(); }

    // ── Render ────────────────────────────────────────────────────────

    public function render(): \Illuminate\View\View
    {
        $otherTreatments = Treatment::active()
            ->where('id', '!=', $this->treatment->id)
            ->orderBy('name')
            ->get(['id', 'name', 'commercial_name']);

        return view('livewire.treatment-edit', [
            'history'          => $this->treatment->posologyHistory()->get(),
            'colors'           => self::COLORS,
            'widgetIcons'      => self::WIDGET_ICONS,
            'otherTreatments'  => $otherTreatments,
        ])->layout('layouts.app', ['title' => $this->treatment->name]);
    }
}
