<?php

namespace App\Livewire;

use App\Models\CalendarEvent;
use App\Models\PersonalEvent;
use App\Services\ActiveProfile;
use App\Services\CalendarService;
use App\Services\EventMoveService;
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
        $event = CalendarEvent::findOrFail($eventId);
        $this->moveToDate = $event->scheduled_date->toDateString();
        $this->showMoveModal = true;
    }

    public function confirmMove(EventMoveService $moveService, CalendarService $calendarService): void
    {
        $this->validate(['moveToDate' => 'required|date']);

        $event = CalendarEvent::findOrFail($this->movingEventId);
        $moveService->move($event, $this->moveToDate);

        $this->showMoveModal = false;
        $this->movingEventId = null;
        $this->loadMonth($calendarService);
        $this->loadDay($calendarService);
    }

    public function cancelMove(): void
    {
        $this->showMoveModal = false;
        $this->movingEventId = null;
        $this->moveToDate = '';
    }

    public function openEventModal(): void
    {
        $date = $this->selectedDate ?? now()->toDateString();
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

    public function deleteEvent(int $id, CalendarService $service): void
    {
        PersonalEvent::findOrFail($id)->delete();
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
