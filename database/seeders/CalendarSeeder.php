<?php

namespace Database\Seeders;

use App\Models\Treatment;
use App\Models\CalendarEvent;
use App\Models\Setting;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class CalendarSeeder extends Seeder
{
    public function run(): void
    {
        $start = Carbon::parse(Setting::get('treatment_start'));
        $end = Carbon::parse(Setting::get('treatment_end'));

        // 1. Générer les événements cycliques (Hôpital, VCR, IT MTTX)
        $cyclicTreatments = Treatment::where('type', 'cyclic')
            ->whereNotNull('frequency_weeks')
            ->whereNotNull('recurrence_start')
            ->get();

        foreach ($cyclicTreatments as $treatment) {
            $current = Carbon::parse($treatment->recurrence_start);
            while ($current->lte($end)) {
                CalendarEvent::create([
                    'treatment_id' => $treatment->id,
                    'scheduled_date' => $current->toDateString(),
                ]);
                $current->addWeeks($treatment->frequency_weeks);
            }
        }

        // 2. Générer les événements pour les traitements liés à un parent
        $linkedTreatments = Treatment::whereNotNull('parent_treatment_id')->get();
        foreach ($linkedTreatments as $child) {
            $days = $child->linked_days ?? 1;
            $parentEvents = CalendarEvent::where('treatment_id', $child->parent_treatment_id)->get();
            foreach ($parentEvents as $parentEvent) {
                $parentDate = Carbon::parse($parentEvent->scheduled_date);
                for ($day = 0; $day < $days; $day++) {
                    CalendarEvent::create([
                        'treatment_id'   => $child->id,
                        'scheduled_date' => $parentDate->copy()->addDays($day)->toDateString(),
                        'parent_event_id' => $parentEvent->id,
                    ]);
                }
            }
        }

        // 3. Récupérer les dates IT MTTX générées (pour exclure les mardis MTX)
        $itMttx = Treatment::where('name', 'IT MTTX')->first();
        $itMttxDates = CalendarEvent::where('treatment_id', $itMttx->id)
            ->pluck('scheduled_date')
            ->map(fn($d) => Carbon::parse($d)->toDateString())
            ->toArray();

        // 3. Générer les MTX (tous les mardis sauf jours IT MTTX)
        $mtx = Treatment::where('name', 'MTX')->first();
        $current = $start->copy()->startOfWeek(Carbon::MONDAY);

        while ($current->lte($end)) {
            // day_of_week=1 => mardi, addDays(1) from Monday = Tuesday
            $tuesday = $current->copy()->addDays($mtx->day_of_week);

            if ($tuesday->gte($start) && $tuesday->lte($end)) {
                $dateStr = $tuesday->toDateString();
                if (!in_array($dateStr, $itMttxDates)) {
                    CalendarEvent::create([
                        'treatment_id' => $mtx->id,
                        'scheduled_date' => $dateStr,
                    ]);
                }
            }
            $current->addWeek();
        }
    }
}
