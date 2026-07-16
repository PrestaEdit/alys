<?php

namespace App\Services;

use App\Models\CalendarEvent;
use App\Models\Treatment;
use Carbon\Carbon;

/**
 * Décale un bloc d'events enfants (mêmes parent_event_id) vers une nouvelle
 * date de début et un moment de la journée (matin/midi/soir), en conservant le
 * nombre total de doses. Les prises "avant" le moment choisi sur le premier
 * jour sont marquées skippées, et le bloc est prolongé au bout de la même
 * quantité de dayparts pour compenser.
 *
 * Utilisé uniquement pour les traitements enfants (parent_treatment_id != null)
 * avec doses par moment (hasDayPartDoses).
 */
class BlockShiftService
{
    /** Ordre canonique des dayparts (croissant dans la journée). */
    private const DAYPARTS = ['morning', 'noon', 'evening'];

    /**
     * @param CalendarEvent $anchor  N'importe quel event du bloc à décaler (celui affiché comme "porteur").
     * @param string        $newDate Nouvelle date du premier jour du bloc.
     * @param string        $moment  'morning' | 'noon' | 'evening' — première prise du bloc décalé.
     */
    public function shift(CalendarEvent $anchor, string $newDate, string $moment): void
    {
        if ($anchor->parent_event_id === null) {
            throw new \InvalidArgumentException('BlockShiftService only handles child events.');
        }
        if (! in_array($moment, self::DAYPARTS, true)) {
            throw new \InvalidArgumentException("Invalid moment: {$moment}");
        }

        $treatment = $anchor->treatment;
        $activeDayparts = $this->activeDayparts($treatment);

        if (empty($activeDayparts) || ! in_array($moment, $activeDayparts, true)) {
            throw new \InvalidArgumentException("Treatment does not use daypart {$moment}");
        }

        $blockEvents = CalendarEvent::where('parent_event_id', $anchor->parent_event_id)
            ->orderBy('scheduled_date')
            ->orderBy('id')
            ->get();

        if ($blockEvents->isEmpty()) return;

        // 1) Shift all existing events by delta (anchor's current date → newDate).
        $anchorPrevDate = $anchor->scheduled_date->toDateString();
        $delta = Carbon::parse($anchorPrevDate)->diffInDays(Carbon::parse($newDate), false);

        foreach ($blockEvents as $event) {
            if ($event->original_date === null) {
                $event->original_date = $event->scheduled_date->toDateString();
            }
            $event->scheduled_date = $event->scheduled_date->copy()->addDays($delta)->toDateString();
            $event->save();
        }

        // 2) Determine how many dayparts are skipped at the start of the shifted block.
        $indexInDay = array_search($moment, $activeDayparts, true);
        $skippedAtStart = $indexInDay; // 0 if starting from first daypart of the day.

        if ($skippedAtStart === 0) {
            return; // Nothing to skip, block just moved.
        }

        $first = $blockEvents->first();
        foreach (array_slice($activeDayparts, 0, $skippedAtStart) as $dp) {
            $first->{'skip_' . $dp} = true;
        }
        $first->save();

        // 3) Auto-extend the block at the end to compensate for the skipped doses.
        $lastDate = Carbon::parse($blockEvents->last()->scheduled_date->toDateString());
        $dosesToAdd = $skippedAtStart;

        while ($dosesToAdd > 0) {
            $lastDate = $lastDate->copy()->addDay();
            $activeCount = count($activeDayparts);

            $extra = CalendarEvent::create([
                'treatment_id'    => $treatment->id,
                'scheduled_date'  => $lastDate->toDateString(),
                'parent_event_id' => $anchor->parent_event_id,
                'is_cancelled'    => false,
            ]);

            if ($dosesToAdd >= $activeCount) {
                // Full day of doses on this extra event.
                $dosesToAdd -= $activeCount;
                continue;
            }

            // Partial day: skip the trailing dayparts so only $dosesToAdd remain active,
            // taken from the start of the day.
            $keep = $dosesToAdd;
            foreach (array_slice($activeDayparts, $keep) as $dp) {
                $extra->{'skip_' . $dp} = true;
            }
            $extra->save();
            $dosesToAdd = 0;
        }
    }

    /**
     * @return list<string> Dayparts définis sur le traitement, dans l'ordre matin→soir.
     */
    private function activeDayparts(Treatment $t): array
    {
        $active = [];
        if ($t->dose_morning !== null) $active[] = 'morning';
        if ($t->dose_noon    !== null) $active[] = 'noon';
        if ($t->dose_evening !== null) $active[] = 'evening';
        return $active;
    }
}
