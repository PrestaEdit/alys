<?php

namespace App\Services;

use App\Models\CalendarEvent;
use App\Models\PosologyHistory;
use App\Models\Profile;
use App\Models\Treatment;

class ImportService
{
    public function __construct(private CryptoService $crypto) {}

    public function parse(string $alysContent, string $keyBase64): array
    {
        try {
            $json = $this->crypto->decrypt($alysContent, $keyBase64);
        } catch (\Throwable $e) {
            throw new \RuntimeException('Decryption failed: ' . $e->getMessage(), 0, $e);
        }

        try {
            $data = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            throw new \RuntimeException('Malformed JSON in alys file: ' . $e->getMessage(), 0, $e);
        }

        if (! isset($data['treatments'], $data['posology_history'], $data['calendar_events'])) {
            throw new \RuntimeException('Malformed export data');
        }

        return $data;
    }

    public function restore(
        string $alysContent,
        string $keyBase64,
        ?array $selectedTreatments = null
    ): void {
        $data = $this->parse($alysContent, $keyBase64);
        $this->applyRestore($data, $selectedTreatments);
    }

    public function restoreFromData(array $data, ?array $selectedTreatments = null): void
    {
        $this->applyRestore($data, $selectedTreatments);
    }

    private function applyRestore(array $data, ?array $selectedTreatments): void
    {
        $profileIdMap = [];
        if (isset($data['profiles'])) {
            $profileIdMap = $this->importProfiles(
                $data['profiles'],
                $data['treatments'],
                $selectedTreatments
            );
        }

        $this->importTreatments($data['treatments'], $profileIdMap, $selectedTreatments);
        $this->importHistory($data['posology_history'], $profileIdMap, $selectedTreatments);
        $this->importEvents($data['calendar_events'], $profileIdMap, $selectedTreatments);
    }

    private function isSelected(array $item, ?array $selectedTreatments, string $nameKey = 'name'): bool
    {
        if ($selectedTreatments === null) {
            return true;
        }
        $key = ($item['profile_id'] ?? 0) . ':' . $item[$nameKey];
        return in_array($key, $selectedTreatments, true);
    }

    /** @return array<int, int> old profile id → new profile id */
    private function importProfiles(array $profiles, array $treatments, ?array $selectedTreatments): array
    {
        $map = [];
        foreach ($profiles as $p) {
            if ($selectedTreatments !== null) {
                $hasSelected = collect($treatments)
                    ->filter(fn($t) => ($t['profile_id'] ?? null) === $p['id'])
                    ->some(fn($t) => in_array($p['id'] . ':' . $t['name'], $selectedTreatments, true));
                if (! $hasSelected) {
                    continue;
                }
            }

            $profile = Profile::updateOrCreate(
                ['name' => $p['name']],
                [
                    'color'           => $p['color'] ?? null,
                    'icon'            => $p['icon'] ?? null,
                    'treatment_start' => $p['treatment_start'] ?? null,
                    'treatment_end'   => $p['treatment_end'] ?? null,
                    'archived_at'     => $p['archived_at'] ?? null,
                ]
            );
            $map[$p['id']] = $profile->id;
        }
        return $map;
    }

    private function resolveProfileId(array $item, array $profileIdMap): ?int
    {
        if (isset($item['profile_id'], $profileIdMap[$item['profile_id']])) {
            return $profileIdMap[$item['profile_id']];
        }
        return app(ActiveProfile::class)->id();
    }

    private function importTreatments(array $treatments, array $profileIdMap, ?array $selectedTreatments): void
    {
        foreach ($treatments as $t) {
            if (! $this->isSelected($t, $selectedTreatments)) {
                continue;
            }

            $profileId = $this->resolveProfileId($t, $profileIdMap);

            Treatment::withoutGlobalScopes()->updateOrCreate(
                ['name' => $t['name'], 'profile_id' => $profileId],
                [
                    'profile_id'       => $profileId,
                    'commercial_name'  => $t['commercial_name'] ?? null,
                    'type'             => $t['type'],
                    'unit'             => $t['unit'],
                    'current_dose'     => $t['current_dose'],
                    'dose_morning'     => $t['dose_morning'] ?? null,
                    'dose_noon'        => $t['dose_noon'] ?? null,
                    'dose_evening'     => $t['dose_evening'] ?? null,
                    'color'            => $t['color'] ?? null,
                    'frequency_weeks'  => $t['frequency_weeks'] ?? null,
                    'day_of_week'      => $t['day_of_week'] ?? null,
                    'recurrence_start' => $t['recurrence_start'] ?? null,
                    'is_medical_act'   => $t['is_medical_act'] ?? false,
                    'requires_fasting' => $t['requires_fasting'] ?? false,
                    'notes'            => $t['notes'] ?? null,
                    'show_widget'      => $t['show_widget'] ?? false,
                    'widget_icon'      => $t['widget_icon'] ?? null,
                    'archived_at'      => $t['archived_at'] ?? null,
                ]
            );
        }
    }

    private function importHistory(array $history, array $profileIdMap, ?array $selectedTreatments): void
    {
        foreach ($history as $h) {
            if (! $this->isSelected($h, $selectedTreatments, 'treatment_name')) {
                continue;
            }

            $profileId = $this->resolveProfileId($h, $profileIdMap);

            $treatment = Treatment::withoutGlobalScopes()
                ->where('name', $h['treatment_name'])
                ->where('profile_id', $profileId)
                ->first();

            if (! $treatment) {
                continue;
            }

            PosologyHistory::withoutGlobalScopes()->firstOrCreate(
                ['treatment_id' => $treatment->id, 'started_at' => $h['started_at']],
                [
                    'profile_id'   => $profileId,
                    'dose'         => $h['dose'] ?? null,
                    'dose_morning' => $h['dose_morning'] ?? null,
                    'dose_noon'    => $h['dose_noon'] ?? null,
                    'dose_evening' => $h['dose_evening'] ?? null,
                    'note'         => $h['note'] ?? null,
                ]
            );
        }
    }

    private function importEvents(array $events, array $profileIdMap, ?array $selectedTreatments): void
    {
        foreach ($events as $e) {
            if (! $this->isSelected($e, $selectedTreatments, 'treatment_name')) {
                continue;
            }

            $profileId = $this->resolveProfileId($e, $profileIdMap);

            $treatment = Treatment::withoutGlobalScopes()
                ->where('name', $e['treatment_name'])
                ->where('profile_id', $profileId)
                ->first();

            if (! $treatment) {
                continue;
            }

            CalendarEvent::withoutGlobalScopes()->firstOrCreate(
                ['treatment_id' => $treatment->id, 'scheduled_date' => $e['scheduled_date']],
                [
                    'profile_id'    => $profileId,
                    'original_date' => $e['original_date'] ?? null,
                    'is_cancelled'  => $e['is_cancelled'] ?? false,
                    'notes'         => $e['notes'] ?? null,
                    'skip_morning'  => $e['skip_morning'] ?? false,
                    'skip_noon'     => $e['skip_noon'] ?? false,
                    'skip_evening'  => $e['skip_evening'] ?? false,
                ]
            );
        }
    }
}
