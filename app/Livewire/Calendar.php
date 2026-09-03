<?php

namespace App\Livewire;

use App\Models\CalendarEvent;
use App\Models\PersonalEvent;
use App\Services\ActiveProfile;
use App\Services\BlockShiftService;
use App\Services\CalendarService;
use App\Services\EventMoveService;
use App\Services\EventSkipService;
use Carbon\Carbon;
use Livewire\Component;

class Calendar extends Component
{
    public int $year;
    public int $month;
    public ?string $selectedDate = null;
    public array $monthEvents = [];
    public array $selectedDayEvents = [];
    public bool $showMoveModal = false;
    public ?int $movingEventId = null;
    public string $moveToDate = '';
    public string $moveToMoment = '';
    /** @var list<string> Dayparts available for the moving event's treatment. */
    public array $moveMomentOptions = [];
    /** Le traitement de l'événement à déplacer est-il récurrent et ré-ancrable ? */
    public bool $moveCanShiftFuture = false;
    /** L'utilisateur veut-il aussi décaler toutes les occurrences suivantes ? */
    public bool $moveShiftFuture = false;

    // Événements personnels
    public bool $showEventModal = false;
    public ?int $editingEventId = null;
    public string $eventTitle = '';
    public string $eventCategory = 'vacances';
    public string $eventColor = '#0ea5e9';
    public string $eventIcon = '🏖️';
    public string $eventStartDate = '';
    public string $eventEndDate = '';
    public string $eventNotes = '';

    // Modale de confirmation de suppression d'un événement personnel
    public bool $showDeleteEventModal = false;
    public ?int $deletingEventId = null;

    public function mount(CalendarService $service): void
    {
        $this->year = now()->year;
        $this->month = now()->month;
        $this->selectedDate = now()->toDateString();
        $this->loadMonth($service);
        $this->loadDay($service);
    }

    public function previousMonth(CalendarService $service): void
    {
        $date = Carbon::create($this->year, $this->month, 1)->subMonth();
        $this->year = $date->year;
        $this->month = $date->month;
        $this->selectedDate = null;
        $this->selectedDayEvents = [];
        $this->loadMonth($service);
    }

    public function nextMonth(CalendarService $service): void
    {
        $date = Carbon::create($this->year, $this->month, 1)->addMonth();
        $this->year = $date->year;
        $this->month = $date->month;
        $this->selectedDate = null;
        $this->selectedDayEvents = [];
        $this->loadMonth($service);
    }

    public function selectDay(string $date, CalendarService $service): void
    {
        $this->selectedDate = $date;
        $this->loadDay($service);
    }

    public function openMoveModal(int $eventId): void
    {
        $this->movingEventId = $eventId;
        $event = CalendarEvent::with('treatment')->findOrFail($eventId);
        $this->moveToDate = $event->scheduled_date->toDateString();

        $this->moveMomentOptions = $this->momentOptionsForEvent($event);
        $this->moveToMoment = $this->moveMomentOptions[0] ?? '';

        // Ré-ancrage possible uniquement pour un événement racine d'un traitement récurrent.
        $this->moveCanShiftFuture = $event->parent_event_id === null
            && in_array($event->treatment->type, ['weekly', 'cyclic'], true);
        $this->moveShiftFuture = false;

        $this->showMoveModal = true;
    }

    public function confirmMove(
        EventMoveService $moveService,
        BlockShiftService $blockShiftService,
        CalendarService $calendarService,
    ): void {
        $this->validate([
            'moveToDate'   => 'required|date',
            'moveToMoment' => 'nullable|in:morning,noon,evening',
        ]);

        $event = CalendarEvent::with('treatment')->findOrFail($this->movingEventId);

        // Traitement enfant à dayparts + moment choisi → shift avec extension.
        // Sinon → shift classique (parent, ou enfant sans daypart).
        if (! empty($this->moveMomentOptions) && $event->parent_event_id !== null) {
            $blockShiftService->shift($event, $this->moveToDate, $this->moveToMoment);
        } else {
            $moveService->move($event, $this->moveToDate);
        }

        if ($this->moveCanShiftFuture && $this->moveShiftFuture) {
            $this->reanchorRecurrence($event->treatment, $this->moveToDate, $event->id);
        }

        $this->showMoveModal = false;
        $this->movingEventId = null;
        $this->moveToMoment = '';
        $this->moveMomentOptions = [];
        $this->moveCanShiftFuture = false;
        $this->moveShiftFuture = false;
        $this->loadMonth($calendarService);
        $this->loadDay($calendarService);
    }

    private function reanchorRecurrence(\App\Models\Treatment $treatment, string $newAnchorDate, int $movingEventId): void
    {
        $today = Carbon::today();
        $newAnchor = Carbon::parse($newAnchorDate);

        $updates = ['recurrence_start' => $newAnchorDate];
        if ($treatment->type === 'weekly') {
            $updates['day_of_week'] = $newAnchor->dayOfWeek;
        }
        $treatment->update($updates);
        $treatment->refresh();

        // Efface les occurrences futures non annulées et jamais déplacées manuellement.
        // Preserve : l'événement qu'on vient de déplacer, les événements annulés, et
        // ceux dont original_date est renseigné (déplacements manuels explicites).
        $treatment->calendarEvents()
            ->where('scheduled_date', '>', $today->toDateString())
            ->where('is_cancelled', false)
            ->whereNull('original_date')
            ->where('id', '!=', $movingEventId)
            ->delete();

        $profile = app(ActiveProfile::class)->get();
        if (! $profile) return;
        $endDate = $profile->treatment_end;
        if (! $endDate) return;

        $freq = max(1, (int) $treatment->frequency_weeks);
        $current = $newAnchor->copy();
        // Avance jusqu'après aujourd'hui (l'événement déplacé peut être aujourd'hui ou dans le passé).
        while ($current->lte($today)) {
            $current->addWeeks($freq);
        }

        while ($current->lte($endDate)) {
            $exists = $treatment->calendarEvents()
                ->whereDate('scheduled_date', $current->toDateString())
                ->exists();
            if (! $exists) {
                CalendarEvent::create([
                    'treatment_id'   => $treatment->id,
                    'scheduled_date' => $current->toDateString(),
                    'is_cancelled'   => false,
                ]);
            }
            $current->addWeeks($freq);
        }
    }

    public function cancelMove(): void
    {
        $this->showMoveModal = false;
        $this->movingEventId = null;
        $this->moveToDate = '';
        $this->moveToMoment = '';
        $this->moveMomentOptions = [];
        $this->moveCanShiftFuture = false;
        $this->moveShiftFuture = false;
    }

    /** @return list<string> */
    private function momentOptionsForEvent(CalendarEvent $event): array
    {
        $t = $event->treatment;
        if ($t->is_medical_act || ! $t->hasDayPartDoses() || $event->parent_event_id === null) {
            return [];
        }
        $options = [];
        if ($t->dose_morning !== null) $options[] = 'morning';
        if ($t->dose_noon    !== null) $options[] = 'noon';
        if ($t->dose_evening !== null) $options[] = 'evening';
        return $options;
    }

    public function toggleDaypartSkip(int $eventId, string $daypart, CalendarService $service): void
    {
        if (! in_array($daypart, ['morning', 'noon', 'evening'], true)) {
            return;
        }
        $event = CalendarEvent::findOrFail($eventId);
        $column = 'skip_' . $daypart;
        $event->{$column} = ! $event->{$column};
        $event->save();

        $this->loadDay($service);
    }

    public function openEventModal(): void
    {
        $date = $this->selectedDate ?? Carbon::create($this->year, $this->month, 1)->toDateString();
        $this->resetEventForm();
        $this->eventStartDate = $date;
        $this->eventEndDate = $date;
        $this->showEventModal = true;
    }

    public function selectCategory(string $category): void
    {
        if (! array_key_exists($category, PersonalEvent::CATEGORIES)) {
            return;
        }
        $this->eventCategory = $category;
        $this->eventIcon = PersonalEvent::CATEGORIES[$category]['icon'];
        $this->eventColor = PersonalEvent::CATEGORIES[$category]['color'];
    }

    public function editEvent(int $id): void
    {
        $event = PersonalEvent::findOrFail($id);
        $this->editingEventId = $event->id;
        $this->eventTitle = $event->title;
        $this->eventCategory = $event->category;
        $this->eventColor = $event->color;
        $this->eventIcon = $event->icon;
        $this->eventStartDate = $event->start_date->toDateString();
        $this->eventEndDate = $event->end_date->toDateString();
        $this->eventNotes = $event->notes ?? '';
        $this->showEventModal = true;
    }

    public function saveEvent(CalendarService $service): void
    {
        $data = $this->validate([
            'eventTitle'     => 'required|string|max:255',
            'eventCategory'  => 'required|in:' . implode(',', array_keys(PersonalEvent::CATEGORIES)),
            'eventColor'     => 'required|string',
            'eventIcon'      => 'required|string',
            'eventStartDate' => 'required|date',
            'eventEndDate'   => 'required|date|after_or_equal:eventStartDate',
        ]);

        $attributes = [
            'title'      => $data['eventTitle'],
            'category'   => $data['eventCategory'],
            'color'      => $data['eventColor'],
            'icon'       => $data['eventIcon'],
            'notes'      => $this->eventNotes !== '' ? $this->eventNotes : null,
            'start_date' => $data['eventStartDate'],
            'end_date'   => $data['eventEndDate'],
        ];

        if ($this->editingEventId !== null) {
            PersonalEvent::findOrFail($this->editingEventId)->update($attributes);
        } else {
            PersonalEvent::create($attributes);
        }

        $this->showEventModal = false;
        $this->resetEventForm();
        $this->loadMonth($service);
        $this->loadDay($service);
    }

    public function openDeleteEventModal(int $id): void
    {
        $this->deletingEventId = $id;
        $this->showDeleteEventModal = true;
    }

    public function cancelDeleteEvent(): void
    {
        $this->showDeleteEventModal = false;
        $this->deletingEventId = null;
    }

    public function confirmDeleteEvent(CalendarService $service): void
    {
        if ($this->deletingEventId !== null) {
            PersonalEvent::findOrFail($this->deletingEventId)->delete();
        }
        $this->showDeleteEventModal = false;
        $this->deletingEventId = null;
        $this->loadMonth($service);
        $this->loadDay($service);
    }

    public function cancelEventModal(): void
    {
        $this->showEventModal = false;
        $this->resetEventForm();
    }

    private function resetEventForm(): void
    {
        $this->editingEventId = null;
        $this->eventTitle = '';
        $this->eventCategory = 'vacances';
        $this->eventColor = PersonalEvent::CATEGORIES['vacances']['color'];
        $this->eventIcon = PersonalEvent::CATEGORIES['vacances']['icon'];
        $this->eventStartDate = '';
        $this->eventEndDate = '';
        $this->eventNotes = '';
        $this->resetErrorBag();
    }

    public function skipOccurrence(int $eventId, EventSkipService $skipService, CalendarService $calendarService): void
    {
        $event = CalendarEvent::findOrFail($eventId);
        $skipService->skip($event);

        $this->loadMonth($calendarService);
        $this->loadDay($calendarService);
    }

    private function loadMonth(CalendarService $service): void
    {
        $this->monthEvents = $service->getEventsForMonth($this->year, $this->month);
    }

    private function loadDay(CalendarService $service): void
    {
        if ($this->selectedDate) {
            $this->selectedDayEvents = $service->getEventsForDay(Carbon::parse($this->selectedDate));
        }
    }

    public function render(): \Illuminate\View\View
    {
        $firstDay = Carbon::create($this->year, $this->month, 1);
        $daysInMonth = $firstDay->daysInMonth;
        $startOffset = ($firstDay->dayOfWeek === 0) ? 6 : $firstDay->dayOfWeek - 1;

        $legend = \App\Models\Treatment::active()->orderByRaw("name = 'Hôpital' DESC")
            ->orderBy('name')
            ->get()
            ->map(fn($t) => [
                'color'        => $t->color,
                'label'        => $t->displayName(),
                'name'         => $t->name,
                'type'         => $t->type,
                'is_medical_act' => $t->is_medical_act,
                'frequency_weeks' => $t->frequency_weeks,
            ])
            ->values()
            ->toArray();

        $profile = app(ActiveProfile::class)->get();
        $profileName = $profile?->name ?? 'Alys';

        // Localized weekday initials, starting Monday, following the active locale.
        $weekStart = Carbon::now()->startOfWeek(Carbon::MONDAY);
        $weekdayHeaders = collect(range(0, 6))
            ->map(fn($i) => mb_strtoupper(mb_substr($weekStart->copy()->addDays($i)->isoFormat('dd'), 0, 1)))
            ->all();

        return view('livewire.calendar', [
            'firstDay' => $firstDay,
            'daysInMonth' => $daysInMonth,
            'startOffset' => $startOffset,
            'monthName' => $firstDay->isoFormat('MMMM YYYY'),
            'weekdayHeaders' => $weekdayHeaders,
            'today' => now()->toDateString(),
            'legend' => $legend,
            'profileName' => $profileName,
            'eventCategories' => array_keys(PersonalEvent::CATEGORIES),
            'eventColors'     => \App\Livewire\TreatmentCreate::COLORS,
            'eventIcons'      => PersonalEvent::ICONS,
        ])->layout('layouts.app', ['title' => __('nav.calendar')]);
    }
}
