<?php

namespace App\Services;

use App\Models\Profile;
use App\Models\Treatment;

class ImportPreviewService
{
    private const DIFF_FIELDS = [
        'commercial_name', 'type', 'unit', 'color', 'archived_at',
        'current_dose', 'dose_morning', 'dose_noon', 'dose_evening',
        'frequency_weeks', 'day_of_week', 'is_medical_act', 'requires_fasting',
    ];

    private const DECIMAL_FIELDS = ['current_dose', 'dose_morning', 'dose_noon', 'dose_evening'];
    private const BOOL_FIELDS    = ['is_medical_act', 'requires_fasting'];
    private const INT_FIELDS     = ['frequency_weeks', 'day_of_week'];

    public function __construct(private ActiveProfile $activeProfile) {}

    public function preview(array $data): array
    {
        $profiles = $data['profiles'] ?? null;

        if ($profiles === null) {
            $profiles = $this->buildLegacyProfiles($data['treatments']);
        }

        $result = [];
        foreach ($profiles as $p) {
            $profileTreatments = array_values(array_filter(
                $data['treatments'],
                fn($t) => ($t['profile_id'] ?? null) === $p['id']
            ));

            $incomingNames = array_column($profileTreatments, 'name');
            $profileExists = Profile::where('name', $p['name'])->exists();

            $treatments = array_map(
                fn($t) => $this->classifyTreatment($t, $p['name']),
                $profileTreatments
            );

            $result[] = [
                'old_id'     => $p['id'],
                'name'       => $p['name'],
                'color'      => $p['color'] ?? null,
                'status'     => $profileExists ? 'existing' : 'new',
                'treatments' => $treatments,
                'local_only' => $this->findLocalOnly($p['name'], $incomingNames),
            ];
        }

        return $result;
    }

    private function buildLegacyProfiles(array &$treatments): array
    {
        $id = $this->activeProfile->id() ?? 0;
        $profile = $id ? Profile::find($id) : null;

        foreach ($treatments as &$t) {
            $t['profile_id'] = $id;
        }

        return [[
            'id'    => $id,
            'name'  => $profile?->name ?? 'Profil actuel',
            'color' => $profile?->color ?? null,
        ]];
    }

    private function classifyTreatment(array $incoming, string $profileName): array
    {
        $existing = Treatment::withoutGlobalScopes()
            ->join('profiles', 'treatments.profile_id', '=', 'profiles.id')
            ->where('treatments.name', $incoming['name'])
            ->where('profiles.name', $profileName)
            ->select('treatments.*')
            ->first();

        if ($existing === null) {
            return [
                'name'        => $incoming['name'],
                'status'      => 'new',
                'incoming'    => $incoming,
                'current'     => null,
                'diff_fields' => [],
            ];
        }

        $diffFields = $this->computeDiffFields($incoming, $existing->toArray());

        return [
            'name'        => $incoming['name'],
            'status'      => empty($diffFields) ? 'unchanged' : 'modified',
            'incoming'    => $incoming,
            'current'     => $existing->toArray(),
            'diff_fields' => $diffFields,
        ];
    }

    private function computeDiffFields(array $incoming, array $current): array
    {
        $diff = [];
        foreach (self::DIFF_FIELDS as $field) {
            $a = $this->normalize($field, $incoming[$field] ?? null);
            $b = $this->normalize($field, $current[$field] ?? null);
            if ($a !== $b) {
                $diff[] = $field;
            }
        }
        return $diff;
    }

    private function normalize(string $field, mixed $value): string
    {
        if ($value === null) {
            return '';
        }
        if (in_array($field, self::DECIMAL_FIELDS, true)) {
            return number_format((float) $value, 2, '.', '');
        }
        if (in_array($field, self::BOOL_FIELDS, true)) {
            return $value ? '1' : '0';
        }
        if (in_array($field, self::INT_FIELDS, true)) {
            return (string)(int) $value;
        }
        return (string) $value;
    }

    private function findLocalOnly(string $profileName, array $incomingNames): array
    {
        return Treatment::withoutGlobalScopes()
            ->join('profiles', 'treatments.profile_id', '=', 'profiles.id')
            ->where('profiles.name', $profileName)
            ->when($incomingNames, fn($q) => $q->whereNotIn('treatments.name', $incomingNames))
            ->select('treatments.name')
            ->pluck('treatments.name')
            ->map(fn($name) => ['name' => $name])
            ->values()
            ->toArray();
    }
}
