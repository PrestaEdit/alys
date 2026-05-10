<?php

use App\Models\Profile;
use App\Models\Treatment;
use App\Services\ActiveProfile;
use App\Services\ImportPreviewService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(DatabaseSeeder::class);
    $this->service = app(ImportPreviewService::class);
});

it('classifies a treatment absent from db as new', function () {
    $data = makePreviewData([
        ['profile_id' => 1, 'name' => 'Nouveau Médicament', 'type' => 'daily',
         'unit' => 'mg', 'current_dose' => '5.00'],
    ]);

    $result = $this->service->preview($data);

    $t = collect($result[0]['treatments'])->firstWhere('name', 'Nouveau Médicament');
    expect($t['status'])->toBe('new')
        ->and($t['diff_fields'])->toBe([])
        ->and($t['current'])->toBeNull();
});

it('classifies an identical treatment as unchanged', function () {
    $profile = Profile::first();
    $treatment = Treatment::withoutGlobalScopes()->where('profile_id', $profile->id)->first();

    $data = makePreviewData([
        [
            'profile_id'      => $profile->id,
            'name'            => $treatment->name,
            'type'            => $treatment->type,
            'unit'            => $treatment->unit,
            'current_dose'    => $treatment->current_dose,
            'dose_morning'    => $treatment->dose_morning,
            'dose_noon'       => $treatment->dose_noon,
            'dose_evening'    => $treatment->dose_evening,
            'color'           => $treatment->color,
            'frequency_weeks' => $treatment->frequency_weeks,
            'day_of_week'     => $treatment->day_of_week,
            'commercial_name' => $treatment->commercial_name,
            'is_medical_act'  => $treatment->is_medical_act,
            'requires_fasting'=> $treatment->requires_fasting,
            'archived_at'     => null,
        ],
    ], $profile);

    $result = $this->service->preview($data);

    $t = collect($result[0]['treatments'])->firstWhere('name', $treatment->name);
    expect($t['status'])->toBe('unchanged')
        ->and($t['diff_fields'])->toBe([]);
});

it('detects a changed dose as modified', function () {
    $profile = Profile::first();
    $treatment = Treatment::withoutGlobalScopes()->where('profile_id', $profile->id)->first();

    $data = makePreviewData([
        [
            'profile_id'   => $profile->id,
            'name'         => $treatment->name,
            'type'         => $treatment->type,
            'unit'         => $treatment->unit,
            'current_dose' => '999.00',
        ],
    ], $profile);

    $result = $this->service->preview($data);

    $t = collect($result[0]['treatments'])->firstWhere('name', $treatment->name);
    expect($t['status'])->toBe('modified')
        ->and($t['diff_fields'])->toContain('current_dose');
});

it('lists db treatments absent from file in local_only', function () {
    $profile = Profile::first();
    $dbTreatments = Treatment::withoutGlobalScopes()
        ->where('profile_id', $profile->id)->pluck('name')->toArray();

    $data = makePreviewData([
        array_merge(
            Treatment::withoutGlobalScopes()->where('profile_id', $profile->id)
                ->first()->toArray(),
            ['profile_id' => $profile->id]
        ),
    ], $profile);

    $result = $this->service->preview($data);

    $localOnlyNames = collect($result[0]['local_only'])->pluck('name')->toArray();
    expect(count($localOnlyNames))->toBe(count($dbTreatments) - 1);
});

it('classifies a profile absent from db as new', function () {
    $data = [
        'profiles' => [
            ['id' => 999, 'name' => 'Profil Inexistant', 'color' => '#ff0000'],
        ],
        'treatments'       => [],
        'posology_history' => [],
        'calendar_events'  => [],
    ];

    $result = $this->service->preview($data);

    expect($result[0]['status'])->toBe('new');
});

it('handles legacy file without profiles section using active profile', function () {
    $data = [
        'treatments' => [
            ['name' => 'Doliprane', 'type' => 'daily', 'unit' => 'mg', 'current_dose' => '500.00'],
        ],
        'posology_history' => [],
        'calendar_events'  => [],
    ];

    $result = $this->service->preview($data);

    expect($result)->toHaveCount(1)
        ->and($result[0]['treatments'])->toHaveCount(1)
        ->and($result[0]['treatments'][0]['status'])->toBe('new');
});

function makePreviewData(array $treatments, ?Profile $profile = null): array
{
    $p = $profile ?? Profile::first();
    return [
        'profiles'         => [['id' => $p->id, 'name' => $p->name, 'color' => $p->color]],
        'treatments'       => $treatments,
        'posology_history' => [],
        'calendar_events'  => [],
    ];
}
