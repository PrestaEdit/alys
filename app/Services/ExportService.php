<?php

namespace App\Services;

use App\Models\CalendarEvent;
use App\Models\PosologyHistory;
use App\Models\Setting;
use App\Models\Treatment;

class ExportService
{
    public function generate(): string
    {
        $settings = Setting::all()->pluck('value', 'key')->toArray();

        $treatments = Treatment::all()->map(fn($t) => [
            'id' => $t->id,
            'name' => $t->name,
            'commercial_name' => $t->commercial_name,
            'type' => $t->type,
            'unit' => $t->unit,
            'current_dose' => $t->current_dose,
            'color' => $t->color,
            'frequency_weeks' => $t->frequency_weeks,
            'day_of_week' => $t->day_of_week,
            'recurrence_start' => $t->recurrence_start?->toDateString(),
        ])->toArray();

        $history = PosologyHistory::with('treatment')
            ->orderBy('started_at')
            ->get()
            ->map(fn($h) => [
                'treatment_name' => $h->treatment->name,
                'dose' => $h->dose,
                'unit' => $h->treatment->unit,
                'note' => $h->note,
                'started_at' => $h->started_at->toDateString(),
            ])->toArray();

        $events = CalendarEvent::with('treatment')
            ->orderBy('scheduled_date')
            ->get()
            ->map(fn($e) => [
                'treatment_name' => $e->treatment->name,
                'scheduled_date' => $e->scheduled_date->toDateString(),
                'original_date' => $e->original_date?->toDateString(),
                'is_cancelled' => $e->is_cancelled,
                'notes' => $e->notes,
            ])->toArray();

        return json_encode([
            'exported_at' => now()->toIso8601String(),
            'settings' => $settings,
            'treatments' => $treatments,
            'posology_history' => $history,
            'calendar_events' => $events,
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }
}
