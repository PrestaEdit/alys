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

        // 2. Générer les événements Dexaméthasone liés aux VCR (J0 à J4)
        $vcr = Treatment::where('name', 'VCR')->first();
        $dexa = Treatment::where('name', 'Dexaméthasone')->first();
        $vcrEvents = CalendarEvent::where('treatment_id', $vcr->id)->get();

        foreach ($vcrEvents as $vcrEvent) {
            $vcrDate = Carbon::parse($vcrEvent->scheduled_date);
            for ($day = 0; $day < 5; $day++) {
                CalendarEvent::create([
                    'treatment_id' => $dexa->id,
                    'scheduled_date' => $vcrDate->copy()->addDays($day)->toDateString(),
                    'parent_event_id' => $vcrEvent->id,
                    'notes' => 'Matin et soir — 1 cachet × 2',
                ]);
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
