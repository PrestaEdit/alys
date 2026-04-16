<?php

namespace App\Livewire;

use App\Models\CalendarEvent;
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
        $this->moveToDate = '';
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

        return view('livewire.calendar', [
            'firstDay' => $firstDay,
            'daysInMonth' => $daysInMonth,
            'startOffset' => $startOffset,
            'monthName' => $firstDay->locale('fr')->isoFormat('MMMM YYYY'),
            'today' => now()->toDateString(),
        ])->layout('layouts.app', ['title' => 'Calendrier']);
    }
}
