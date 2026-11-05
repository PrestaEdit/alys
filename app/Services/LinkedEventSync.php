<?php

namespace App\Services;

use App\Models\CalendarEvent;
use App\Models\Treatment;
use Carbon\Carbon;

/**
 * Garantit qu'un traitement lié (parent_treatment_id) possède bien son bloc de
 * `linked_days` occurrences pour chaque occurrence à venir de son parent.
 *
 * Les occurrences d'un parent peuvent être supprimées puis recréées (recalcul
 * de la récurrence, ré-ancrage lors d'un déplacement « et les suivantes »…).
 * La FK parent_event_id étant en nullOnDelete, les enfants se retrouvaient
 * alors orphelins (affichés aux anciennes dates, puis purgés) et les nouvelles
 * occurrences du parent n'avaient plus d'enfants : le traitement lié semblait
 * ne plus suivre son parent. Ce service répare cet état de façon idempotente.
 */
class LinkedEventSync
{
    /** Resynchronise tous les traitements liés à ce parent. */
    public function syncChildrenOf(Treatment $parent): void
    {
        Treatment::withoutGlobalScopes()
            ->whereNull('archived_at')
            ->where('parent_treatment_id', $parent->id)
            ->get()
            ->each(fn (Treatment $child) => $this->syncChild($child));
    }

    /** Resynchronise tous les traitements liés du profil actif. */
    public function syncAll(): void
    {
        Treatment::active()
            ->whereNotNull('parent_treatment_id')
            ->get()
            ->each(fn (Treatment $child) => $this->syncChild($child));
    }

    public function syncChild(Treatment $child): void
    {
        if ($child->parent_treatment_id === null) {
            return;
        }

        $today = Carbon::today();
        $todayStr = $today->toDateString();
        $days = max(1, (int) ($child->linked_days ?? 1));

        // 1) Orphelins futurs : un event de traitement lié sans parent_event_id
        //    n'a pas de sens (parent supprimé, ou résidu d'une ancienne
        //    génération autonome). Le passé est conservé comme historique.
        CalendarEvent::withoutGlobalScopes()
            ->where('treatment_id', $child->id)
            ->whereNull('parent_event_id')
            ->whereDate('scheduled_date', '>', $todayStr)
            ->delete();

        // 2) Occurrences du parent dont le bloc touche aujourd'hui ou le futur
        //    et qui n'ont aucun event pour ce traitement lié → on (re)génère
        //    les jours restants du bloc (jamais de dates passées).
        $parentEvents = CalendarEvent::withoutGlobalScopes()
            ->where('treatment_id', $child->parent_treatment_id)
            ->where('is_cancelled', false)
            ->whereDate('scheduled_date', '>=', $today->copy()->subDays($days - 1)->toDateString())
            ->whereDoesntHave('children', fn ($q) => $q
                ->withoutGlobalScopes()
                ->where('treatment_id', $child->id))
            ->get();

        foreach ($parentEvents as $parentEvent) {
            $base = Carbon::parse($parentEvent->scheduled_date);
            for ($day = 0; $day < $days; $day++) {
                $date = $base->copy()->addDays($day);
                if ($date->lt($today)) {
                    continue;
                }
                CalendarEvent::withoutGlobalScopes()->create([
                    'profile_id'      => $child->profile_id,
                    'treatment_id'    => $child->id,
                    'scheduled_date'  => $date->toDateString(),
                    'parent_event_id' => $parentEvent->id,
                    'is_cancelled'    => false,
                ]);
            }
        }
    }
}
