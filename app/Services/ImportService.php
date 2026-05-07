<?php

namespace App\Services;

use App\Models\CalendarEvent;
use App\Models\PosologyHistory;
use App\Models\Treatment;

class ImportService
{
    public function __construct(private CryptoService $crypto) {}

    public function restore(string $alysContent, string $devicePrivatePem): void
    {
        try {
            $json = $this->crypto->decrypt($alysContent, $devicePrivatePem);
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

        $this->importTreatments($data['treatments']);
        $this->importHistory($data['posology_history']);
        $this->importEvents($data['calendar_events']);
    }

    private function importTreatments(array $treatments): void
    {
        foreach ($treatments as $t) {
            Treatment::updateOrCreate(
                ['name' => $t['name']],
                [
                    'commercial_name'  => $t['commercial_name'] ?? null,
                    'type'             => $t['type'],
                    'unit'             => $t['unit'],
                    'current_dose'     => $t['current_dose'],
                    'color'            => $t['color'] ?? null,
                    'frequency_weeks'  => $t['frequency_weeks'] ?? null,
                    'day_of_week'      => $t['day_of_week'] ?? null,
                    'recurrence_start' => $t['recurrence_start'] ?? null,
                ]
            );
        }
    }

    private function importHistory(array $history): void
    {
        foreach ($history as $h) {
            $treatment = Treatment::where('name', $h['treatment_name'])->first();
            if (! $treatment) {
                continue;
            }

            PosologyHistory::firstOrCreate(
                [
                    'treatment_id' => $treatment->id,
                    'started_at'   => $h['started_at'],
                ],
                [
                    'dose' => $h['dose'],
                    'note' => $h['note'] ?? null,
                ]
            );
        }
    }

    private function importEvents(array $events): void
    {
        foreach ($events as $e) {
            $treatment = Treatment::where('name', $e['treatment_name'])->first();
            if (! $treatment) {
                continue;
            }

            CalendarEvent::firstOrCreate(
                [
                    'treatment_id'   => $treatment->id,
                    'scheduled_date' => $e['scheduled_date'],
                ],
                [
                    'original_date' => $e['original_date'] ?? null,
                    'is_cancelled'  => $e['is_cancelled'] ?? false,
                    'notes'         => $e['notes'] ?? null,
                ]
            );
        }
    }
}
