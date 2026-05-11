<?php

namespace App\Services;

use App\Models\CalendarEvent;
use App\Models\PosologyHistory;
use App\Models\Profile;
use App\Models\Setting;
use App\Models\Treatment;

class ExportService
{
    public function generate(array $selectedTreatmentIds = []): string
    {
        $filtering = ! empty($selectedTreatmentIds);

        $settings = Setting::all()->pluck('value', 'key')->toArray();

        $treatmentsQuery = Treatment::withoutGlobalScopes();
        if ($filtering) {
            $treatmentsQuery->whereIn('id', $selectedTreatmentIds);
        }
        $treatmentModels = $treatmentsQuery->get();

        $profileIds = $filtering
            ? $treatmentModels->pluck('profile_id')->unique()->values()->all()
            : [];

        // Profile has no global scope; Profile::query() already spans all profiles.
        $profilesQuery = Profile::query();
        if ($filtering) {
            $profilesQuery->whereIn('id', $profileIds);
        }
        $profiles = $profilesQuery->get()->map(fn($p) => [
            'id'              => $p->id,
            'name'            => $p->name,
            'color'           => $p->color,
            'icon'            => $p->icon,
            'treatment_start' => $p->treatment_start?->toDateString(),
            'treatment_end'   => $p->treatment_end?->toDateString(),
            'archived_at'     => $p->archived_at?->toIso8601String(),
        ])->toArray();

        $treatments = $treatmentModels->map(fn($t) => [
            'profile_id'       => $t->profile_id,
            'name'             => $t->name,
            'commercial_name'  => $t->commercial_name,
            'type'             => $t->type,
            'unit'             => $t->unit,
            'current_dose'     => $t->current_dose,
            'dose_morning'     => $t->dose_morning,
            'dose_noon'        => $t->dose_noon,
            'dose_evening'     => $t->dose_evening,
            'color'            => $t->color,
            'frequency_weeks'  => $t->frequency_weeks,
            'day_of_week'      => $t->day_of_week,
            'recurrence_start' => $t->recurrence_start?->toDateString(),
            'is_medical_act'   => $t->is_medical_act,
            'requires_fasting' => $t->requires_fasting,
            'notes'            => $t->notes,
            'show_widget'      => $t->show_widget,
            'widget_icon'      => $t->widget_icon,
            'archived_at'      => $t->archived_at?->toIso8601String(),
        ])->toArray();

        $historyQuery = PosologyHistory::withoutGlobalScopes()->with('treatment')->orderBy('started_at');
        if ($filtering) {
            $historyQuery->whereIn('treatment_id', $selectedTreatmentIds);
        }
        $history = $historyQuery->get()->map(fn($h) => [
            'profile_id'     => $h->profile_id,
            'treatment_name' => $h->treatment->name,
            'dose'           => $h->dose,
            'dose_morning'   => $h->dose_morning,
            'dose_noon'      => $h->dose_noon,
            'dose_evening'   => $h->dose_evening,
            'note'           => $h->note,
            'started_at'     => $h->started_at->toDateString(),
        ])->toArray();

        $eventsQuery = CalendarEvent::withoutGlobalScopes()->with('treatment')->orderBy('scheduled_date');
        if ($filtering) {
            $eventsQuery->whereIn('treatment_id', $selectedTreatmentIds);
        }
        $events = $eventsQuery->get()->map(fn($e) => [
            'profile_id'     => $e->profile_id,
            'treatment_name' => $e->treatment->name,
            'scheduled_date' => $e->scheduled_date->toDateString(),
            'original_date'  => $e->original_date?->toDateString(),
            'is_cancelled'   => $e->is_cancelled,
            'notes'          => $e->notes,
        ])->toArray();

        return json_encode([
            'exported_at'      => now()->toIso8601String(),
            'settings'         => $settings,
            'profiles'         => $profiles,
            'treatments'       => $treatments,
            'posology_history' => $history,
            'calendar_events'  => $events,
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    }

    public function generateEncrypted(string $keyBase64, array $selectedTreatmentIds = []): string
    {
        $json = $this->generate($selectedTreatmentIds);
        return app(\App\Services\CryptoService::class)->encrypt($json, $keyBase64);
    }
}
