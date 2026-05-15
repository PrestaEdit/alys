<?php

namespace App\Livewire;

use App\Models\CalendarEvent;
use App\Services\CalendarService;
use App\Services\EventMoveService;
use App\Services\ActiveProfile;
use Carbon\Carbon;
use Livewire\Component;

class Calendar extends Component
{
    public const FASTING_SUBJECT_FALLBACK = 'Ce traitement';

    public int $year;
    public int $month;
    public ?string $selectedDate = null;
    public array $monthEvents = [];
    public array $selectedDayEvents = [];
    public bool $showMoveModal = false;
    public ?int $movingEventId = null;
    public string $moveToDate = '';
    public string $activeProfileName = self::FASTING_SUBJECT_FALLBACK;

    public function mount(CalendarService $service, ActiveProfile $activeProfile): void
    {
        $this->year = now()->year;
        $this->month = now()->month;
        $this->selectedDate = now()->toDateString();
        $this->activeProfileName = $activeProfile->get()?->name ?: self::FASTING_SUBJECT_FALLBACK;
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

        return view('livewire.calendar', [
            'firstDay' => $firstDay,
            'daysInMonth' => $daysInMonth,
            'startOffset' => $startOffset,
            'monthName' => $firstDay->locale('fr')->isoFormat('MMMM YYYY'),
            'today' => now()->toDateString(),
            'legend' => $legend,
        ])->layout('layouts.app', ['title' => 'Calendrier']);
    }
}
