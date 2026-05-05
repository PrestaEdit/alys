<?php

namespace App\Livewire;

use App\Models\CalendarEvent;
use App\Models\PosologyHistory;
use App\Models\Setting;
use App\Models\Treatment;
use Carbon\Carbon;
use Livewire\Component;

class TreatmentCreate extends Component
{
    // General info
    public string $name = '';
    public string $commercialName = '';
    public string $type = 'daily';
    public bool $isMedicalAct = false;
    public bool $requiresFasting = false;
    public string $unit = '';
    public string $color = '#6366f1';

    // Linked treatment
    public ?int $parentTreatmentId = null;
    public int $linkedDays = 1;

    // Widget
    public bool $showWidget = false;
    public string $widgetIcon = '💊';

    // Recurrence (cyclic)
    public int $frequencyWeeks = 4;
    public string $recurrenceStart = '';

    // Posology mode: single | dayparts
    public string $dosageMode = 'single';
    public float $currentDose = 0;
    public ?float $doseMorning = null;
    public ?float $doseNoon = null;
    public ?float $doseEvening = null;

    public const WIDGET_ICONS = ['🏥', '💉', '🔬', '💊', '🧪', '🩺', '🩹', '❤️', '🫀', '🧬'];

    public const COLORS = [
        '#3b82f6', '#10b981', '#ef4444', '#8b5cf6',
        '#0ea5e9', '#f97316', '#f59e0b', '#ec4899',
    ];

    // ── Switch mode ──────────────────────────────────────────────────────

    public function updatedDosageMode(string $value): void
    {
        if ($value === 'dayparts') {
            $this->doseMorning ??= 0;
            $this->doseNoon    ??= 0;
            $this->doseEvening ??= 0;
        }
    }

    // ── Single dose ──────────────────────────────────────────────────────

    public function increment(): void
    {
        $step = $this->unit === 'ml' ? 0.1 : 1;
        $this->currentDose = round($this->currentDose + $step, 2);
    }

    public function decrement(): void
    {
        $step = $this->unit === 'ml' ? 0.1 : 1;
        $this->currentDose = max(0, round($this->currentDose - $step, 2));
    }

    // ── Day-part doses ───────────────────────────────────────────────────

    public function incrementMorning(): void
    {
        $step = $this->unit === 'ml' ? 0.1 : 1;
        $this->doseMorning = round(($this->doseMorning ?? 0) + $step, 2);
    }

    public function decrementMorning(): void
    {
        $step = $this->unit === 'ml' ? 0.1 : 1;
        $this->doseMorning = max(0, round(($this->doseMorning ?? 0) - $step, 2));
    }

    public function incrementNoon(): void
    {
        $step = $this->unit === 'ml' ? 0.1 : 1;
        $this->doseNoon = round(($this->doseNoon ?? 0) + $step, 2);
    }

    public function decrementNoon(): void
    {
        $step = $this->unit === 'ml' ? 0.1 : 1;
        $this->doseNoon = max(0, round(($this->doseNoon ?? 0) - $step, 2));
    }

    public function incrementEvening(): void
    {
        $step = $this->unit === 'ml' ? 0.1 : 1;
        $this->doseEvening = round(($this->doseEvening ?? 0) + $step, 2);
    }

    public function decrementEvening(): void
    {
        $step = $this->unit === 'ml' ? 0.1 : 1;
        $this->doseEvening = max(0, round(($this->doseEvening ?? 0) - $step, 2));
    }

    // ── Frequency / linked days ──────────────────────────────────────────

    public function incrementFrequency(): void { $this->frequencyWeeks++; }

    public function decrementFrequency(): void
    {
        $this->frequencyWeeks = max(1, $this->frequencyWeeks - 1);
    }

    public function incrementLinkedDays(): void { $this->linkedDays++; }

    public function decrementLinkedDays(): void
    {
        $this->linkedDays = max(1, $this->linkedDays - 1);
    }

    // ── Save ─────────────────────────────────────────────────────────────

    public function save(): void
    {
        $rules = [
            'name'              => 'required|string|max:255',
            'type'              => 'required|in:daily,weekly,cyclic',
            'color'             => 'required|string',
            'unit'              => 'nullable|string|max:50',
            'parentTreatmentId' => 'nullable|integer|exists:treatments,id',
            'linkedDays'        => 'required|integer|min:1',
        ];

        if ($this->type === 'cyclic') {
            $rules['frequencyWeeks']  = 'required|integer|min:1';
            $rules['recurrenceStart'] = 'nullable|date';
        }

        $this->validate($rules);

        $treatmentData = [
            'name'                => $this->name,
            'commercial_name'     => $this->commercialName ?: null,
            'type'                => $this->type,
            'is_medical_act'      => $this->isMedicalAct,
            'requires_fasting'    => $this->requiresFasting,
            'color'               => $this->color,
            'parent_treatment_id' => $this->parentTreatmentId,
            'linked_days'         => $this->parentTreatmentId ? $this->linkedDays : null,
            'show_widget'         => $this->showWidget,
            'widget_icon'         => $this->showWidget ? $this->widgetIcon : null,
        ];

        if (!$this->isMedicalAct) {
            $treatmentData['unit'] = $this->unit ?: null;

            if ($this->dosageMode === 'dayparts') {
                $treatmentData['dose_morning'] = $this->doseMorning;
                $treatmentData['dose_noon']    = $this->doseNoon;
                $treatmentData['dose_evening'] = $this->doseEvening;
            } else {
                $treatmentData['current_dose'] = $this->currentDose;
            }
        }

        if ($this->type === 'cyclic') {
            $treatmentData['frequency_weeks'] = $this->frequencyWeeks;
            if ($this->recurrenceStart) {
                $treatmentData['recurrence_start'] = $this->recurrenceStart;
            }
        }

        $treatment = Treatment::create($treatmentData);

        // Initial posology history entry
        if (!$this->isMedicalAct) {
            if ($this->dosageMode === 'dayparts' && ($this->doseMorning || $this->doseNoon || $this->doseEvening)) {
                PosologyHistory::create([
                    'treatment_id' => $treatment->id,
                    'dose_morning' => $this->doseMorning,
                    'dose_noon'    => $this->doseNoon,
                    'dose_evening' => $this->doseEvening,
                    'started_at'   => today()->toDateString(),
                ]);
            } elseif ($this->dosageMode === 'single' && $this->currentDose > 0) {
                PosologyHistory::create([
                    'treatment_id' => $treatment->id,
                    'dose'         => $this->currentDose,
                    'started_at'   => today()->toDateString(),
                ]);
            }
        }

        if ($this->type === 'cyclic' && $this->recurrenceStart) {
            $this->generateCyclicEvents($treatment);
        }

        if ($this->parentTreatmentId) {
            $this->generateLinkedEvents($treatment);
        }

        session()->flash('success', 'Traitement créé avec succès.');
        $this->redirect(route('treatments'), navigate: false);
    }

    private function generateCyclicEvents(Treatment $treatment): void
    {
        $endDateStr = Setting::get('treatment_end');
        if (!$endDateStr) return;

        $endDate = Carbon::parse($endDateStr);
        $start   = Carbon::parse($this->recurrenceStart);
        $today   = Carbon::today();

        $current = $start->copy();
        if ($current->lt($today)) {
            $diff = $today->diffInDays($current);
            $weeksAhead = (int) ceil($diff / (7 * $this->frequencyWeeks));
            $current->addWeeks($weeksAhead * $this->frequencyWeeks);
        }

        while ($current->lte($endDate)) {
            CalendarEvent::create([
                'treatment_id'   => $treatment->id,
                'scheduled_date' => $current->toDateString(),
                'is_cancelled'   => false,
            ]);
            $current->addWeeks($this->frequencyWeeks);
        }
    }

    private function generateLinkedEvents(Treatment $treatment): void
    {
        $today = Carbon::today()->toDateString();
        $parentEvents = CalendarEvent::where('treatment_id', $this->parentTreatmentId)
            ->where('scheduled_date', '>', $today)
            ->where('is_cancelled', false)
            ->get();

        foreach ($parentEvents as $parentEvent) {
            $base = Carbon::parse($parentEvent->scheduled_date);
            for ($day = 0; $day < $this->linkedDays; $day++) {
                CalendarEvent::create([
                    'treatment_id'    => $treatment->id,
                    'scheduled_date'  => $base->copy()->addDays($day)->toDateString(),
                    'parent_event_id' => $parentEvent->id,
                    'is_cancelled'    => false,
                ]);
            }
        }
    }

    public function render(): \Illuminate\View\View
    {
        $otherTreatments = Treatment::orderBy('name')->get(['id', 'name', 'commercial_name']);

        return view('livewire.treatment-create', [
            'colors'          => self::COLORS,
            'widgetIcons'     => self::WIDGET_ICONS,
            'otherTreatments' => $otherTreatments,
        ])->layout('layouts.app', ['title' => 'Nouveau traitement']);
    }
}
