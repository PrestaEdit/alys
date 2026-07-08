<?php

namespace App\Livewire;

use App\Models\CalendarEvent;
use App\Models\PosologyHistory;
use App\Models\Setting;
use App\Models\Treatment;
use App\Support\MedicalIcons;
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

    // Weekly scheduling
    public int $dayOfWeek = 0; // 0=Lundi … 6=Dimanche

    // Widget
    public bool $showWidget = false;
    public string $widgetIcon = 'pill';

    // Recurrence (cyclic)
    public int $frequencyWeeks = 4;
    public string $recurrenceStart = '';

    // Posology mode: single | dayparts | interval
    public string $dosageMode = 'single';
    public float $currentDose = 0;
    public ?float $doseMorning = null;
    public ?float $doseNoon = null;
    public ?float $doseEvening = null;
    public int $timesPerDay = 4;

    public int $step = 1;

    // Notifications
    public bool $notificationEnabled = false;
    public string $notificationTimeMorning = '08:00';
    public string $notificationTimeNoon = '12:30';
    public string $notificationTimeEvening = '20:00';

    public const WIDGET_ICONS = MedicalIcons::KEYS;

    public const COLORS = [
        '#3b82f6', '#10b981', '#ef4444', '#8b5cf6',
        '#0ea5e9', '#f97316', '#f59e0b', '#ec4899',
    ];

    // ── Wizard navigation ─────────────────────────────────────────────────

    public function applicableSteps(): array
    {
        $steps = [1, 2];
        if (!$this->isMedicalAct) $steps[] = 3;
        if ($this->type === 'cyclic' || $this->type === 'weekly') $steps[] = 4;
        $steps[] = 6; // Notifications
        $steps[] = 5; // Récapitulatif
        return $steps;
    }

    public function nextStep(): void
    {
        $this->validateCurrentStep();

        $applicable    = $this->applicableSteps();
        $currentIndex  = array_search($this->step, $applicable);

        if ($currentIndex !== false && isset($applicable[$currentIndex + 1])) {
            $this->step = $applicable[$currentIndex + 1];
        }
    }

    public function prevStep(): void
    {
        $applicable   = $this->applicableSteps();
        $currentIndex = array_search($this->step, $applicable);

        if ($currentIndex > 0) {
            $this->step = $applicable[$currentIndex - 1];
        }
    }

    public function stepLabel(): string
    {
        return match ($this->step) {
            1       => __('treatments.step_basic_info'),
            2       => __('treatments.step_widget'),
            3       => __('treatments.step_posology'),
            4       => $this->type === 'weekly' ? __('treatments.step_scheduling') : __('treatments.step_recurrence'),
            6       => __('treatments.step_notifications'),
            5       => __('treatments.step_summary'),
            default => '',
        };
    }

    private function validateCurrentStep(): void
    {
        if ($this->step === 1) {
            $this->validate([
                'name'  => 'required|string|max:255',
                'type'  => 'required|in:daily,weekly,cyclic',
                'color' => 'required|string',
            ], [
                'name.required'   => __('treatments.validation_name_required'),
                'name.max'        => __('treatments.validation_name_max'),
                'type.required'   => __('treatments.validation_type_required'),
                'type.in'         => __('treatments.validation_type_in'),
                'color.required'  => __('treatments.validation_color_required'),
            ]);
        } elseif ($this->step === 6) {
            if ($this->notificationEnabled) {
                if (!$this->isMedicalAct && $this->dosageMode === 'dayparts') {
                    if (!$this->notificationTimeMorning && !$this->notificationTimeNoon && !$this->notificationTimeEvening) {
                        $this->addError('notificationTimeMorning', __('treatments.validation_notif_at_least_one'));
                        throw new \Livewire\Exceptions\ValidationException(
                            app(\Illuminate\Contracts\Validation\Factory::class)->make([], [])
                        );
                    }
                } else {
                    $this->validate(
                        ['notificationTimeMorning' => 'required|date_format:H:i'],
                        ['notificationTimeMorning.required'    => __('treatments.validation_notif_time_required'),
                         'notificationTimeMorning.date_format' => __('treatments.validation_notif_time_format')]
                    );
                }
            }
        } elseif ($this->step === 4) {
            $rules = ['frequencyWeeks' => 'required|integer|min:1'];
            $messages = [
                'frequencyWeeks.required' => __('treatments.validation_frequency_required'),
                'frequencyWeeks.min'      => __('treatments.validation_frequency_min'),
            ];
            if ($this->type === 'weekly') {
                $rules['dayOfWeek'] = 'required|integer|between:0,6';
            } else {
                $rules['recurrenceStart']    = 'nullable|date';
                $messages['recurrenceStart.date'] = __('treatments.validation_start_date');
            }
            $this->validate($rules, $messages);
        }
    }

    // ── Switch mode ──────────────────────────────────────────────────────

    public function updatedDosageMode(string $value): void
    {
        if ($value === 'dayparts') {
            $this->doseMorning ??= 0;
            $this->doseNoon    ??= 0;
            $this->doseEvening ??= 0;
        }
    }

    public function updatedIsMedicalAct(): void
    {
        if (!in_array($this->step, $this->applicableSteps())) {
            $this->step = 1;
        }
    }

    public function updatedType(): void
    {
        if (!in_array($this->step, $this->applicableSteps())) {
            $this->step = 1;
        }
    }

    // ── Single / interval dose ───────────────────────────────────────────

    public function increment(): void
    {
        $step = $this->unit === 'ml' ? 0.1 : 0.5;
        $this->currentDose = round($this->currentDose + $step, 2);
    }

    public function decrement(): void
    {
        $step = $this->unit === 'ml' ? 0.1 : 0.5;
        $this->currentDose = max(0, round($this->currentDose - $step, 2));
    }

    public function incrementTimesPerDay(): void
    {
        $this->timesPerDay = min(24, $this->timesPerDay + 1);
    }

    public function decrementTimesPerDay(): void
    {
        $this->timesPerDay = max(2, $this->timesPerDay - 1);
    }

    // ── Day-part doses ───────────────────────────────────────────────────

    public function incrementMorning(): void
    {
        $step = $this->unit === 'ml' ? 0.1 : 0.5;
        $this->doseMorning = round(($this->doseMorning ?? 0) + $step, 2);
    }

    public function decrementMorning(): void
    {
        $step = $this->unit === 'ml' ? 0.1 : 0.5;
        $this->doseMorning = max(0, round(($this->doseMorning ?? 0) - $step, 2));
    }

    public function incrementNoon(): void
    {
        $step = $this->unit === 'ml' ? 0.1 : 0.5;
        $this->doseNoon = round(($this->doseNoon ?? 0) + $step, 2);
    }

    public function decrementNoon(): void
    {
        $step = $this->unit === 'ml' ? 0.1 : 0.5;
        $this->doseNoon = max(0, round(($this->doseNoon ?? 0) - $step, 2));
    }

    public function incrementEvening(): void
    {
        $step = $this->unit === 'ml' ? 0.1 : 0.5;
        $this->doseEvening = round(($this->doseEvening ?? 0) + $step, 2);
    }

    public function decrementEvening(): void
    {
        $step = $this->unit === 'ml' ? 0.1 : 0.5;
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

        $this->validate($rules, [
            'name.required'              => __('treatments.validation_name_required'),
            'name.max'                   => __('treatments.validation_name_max'),
            'type.required'              => __('treatments.validation_type_required'),
            'type.in'                    => __('treatments.validation_type_in'),
            'color.required'             => __('treatments.validation_color_required'),
            'linkedDays.required'        => __('treatments.validation_linked_days_required'),
            'linkedDays.min'             => __('treatments.validation_linked_days_min'),
            'frequencyWeeks.required'    => __('treatments.validation_frequency_required'),
            'frequencyWeeks.min'         => __('treatments.validation_frequency_min'),
            'recurrenceStart.date'       => __('treatments.validation_start_date'),
            'parentTreatmentId.exists'   => __('treatments.validation_parent_exists'),
        ]);

        $treatmentData = [
            'name'                => $this->name,
            'commercial_name'     => $this->commercialName ?: null,
            'type'                => $this->type,
            'is_medical_act'      => $this->isMedicalAct,
            'requires_fasting'    => $this->requiresFasting,
            'color'               => $this->color,
            'parent_treatment_id' => $this->parentTreatmentId,
            'linked_days'         => $this->parentTreatmentId ? $this->linkedDays : null,
            'show_widget'               => $this->showWidget,
            'widget_icon'               => $this->showWidget ? $this->widgetIcon : null,
            'notification_enabled'      => $this->notificationEnabled,
            'notification_time_morning' => $this->notificationEnabled ? ($this->notificationTimeMorning ?: null) : null,
            'notification_time_noon'    => ($this->notificationEnabled && $this->dosageMode === 'dayparts') ? ($this->notificationTimeNoon ?: null) : null,
            'notification_time_evening' => ($this->notificationEnabled && $this->dosageMode === 'dayparts') ? ($this->notificationTimeEvening ?: null) : null,
        ];

        if (!$this->isMedicalAct) {
            $treatmentData['unit'] = $this->unit ?: null;

            if ($this->dosageMode === 'dayparts') {
                $treatmentData['dose_morning'] = $this->doseMorning;
                $treatmentData['dose_noon']    = $this->doseNoon;
                $treatmentData['dose_evening'] = $this->doseEvening;
            } elseif ($this->dosageMode === 'interval') {
                $treatmentData['current_dose']  = $this->currentDose;
                $treatmentData['times_per_day'] = $this->timesPerDay;
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

        if ($this->type === 'weekly') {
            $treatmentData['day_of_week']     = $this->dayOfWeek;
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
            } elseif ($this->dosageMode === 'interval' && $this->currentDose > 0) {
                PosologyHistory::create([
                    'treatment_id'  => $treatment->id,
                    'dose'          => $this->currentDose,
                    'times_per_day' => $this->timesPerDay,
                    'started_at'    => today()->toDateString(),
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

        if ($this->type === 'weekly') {
            $this->generateWeeklyEvents($treatment);
        }

        if ($this->parentTreatmentId) {
            $this->generateLinkedEvents($treatment);
        }

        app(\App\Services\NotificationScheduler::class)->scheduleForTreatment($treatment);

        session()->flash('success', __('treatments.created'));
        $this->redirect(route('treatments'), navigate: false);
    }

    private function generateWeeklyEvents(Treatment $treatment): void
    {
        $profile = app(\App\Services\ActiveProfile::class)->get();
        if (!$profile) return;

        $endDate = $profile->treatment_end;
        $start   = $this->recurrenceStart ? Carbon::parse($this->recurrenceStart) : Carbon::today();

        // Position on the correct day of week on or after $start
        $current = $start->copy()->startOfWeek(Carbon::MONDAY)->addDays($this->dayOfWeek);
        if ($current->lt($start)) {
            $current->addWeeks($this->frequencyWeeks);
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

    private function generateCyclicEvents(Treatment $treatment): void
    {
        $profile = app(\App\Services\ActiveProfile::class)->get();
        if (! $profile) return;

        $endDate = $profile->treatment_end;
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
        $otherTreatments = Treatment::active()->orderBy('name')->get(['id', 'name', 'commercial_name']);

        return view('livewire.treatment-create', [
            'colors'          => self::COLORS,
            'widgetIcons'     => self::WIDGET_ICONS,
            'otherTreatments' => $otherTreatments,
            'applicableSteps' => $this->applicableSteps(),
            'stepLabel'       => $this->stepLabel(),
        ])->layout('layouts.app', ['title' => __('treatments.title_create')]);
    }
}
