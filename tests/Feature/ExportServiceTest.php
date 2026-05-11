<?php

use App\Services\ExportService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(fn() => $this->seed(DatabaseSeeder::class));

it('exports all data when no filter given', function () {
    $service = new ExportService();
    $json = $service->generate();
    $data = json_decode($json, true);

    expect($data['treatments'])->not->toBeEmpty();
    expect($data['profiles'])->not->toBeEmpty();
});

it('exports only selected treatments and their profile', function () {
    $service = new ExportService();

    $treatment = \App\Models\Treatment::withoutGlobalScopes()->first();

    $filtered = json_decode($service->generate([$treatment->id]), true);

    expect($filtered['treatments'])->toHaveCount(1);
    expect($filtered['treatments'][0]['name'])->toBe($treatment->name);
});

it('excludes profiles with no selected treatments', function () {
    // Seed a second profile with its own treatment
    $profile2 = \App\Models\Profile::create([
        'name' => 'Profil B', 'color' => '#10b981', 'icon' => 'B',
    ]);
    app(\App\Services\ActiveProfile::class)->set($profile2->id);
    $treatment2 = \App\Models\Treatment::create(['name' => 'Médicament B', 'type' => 'daily', 'unit' => 'mg', 'current_dose' => 100]);
    app(\App\Services\ActiveProfile::class)->set(\App\Models\Profile::where('name', 'Alys')->first()->id);

    $service = new ExportService();
    // Only export treatment2 (which belongs to profile2)
    $data = json_decode($service->generate([$treatment2->id]), true);

    $profileNames = array_column($data['profiles'], 'name');
    expect($profileNames)->not->toContain('Alys');
    expect($profileNames)->toContain('Profil B');
});

it('always exports settings regardless of filter', function () {
    \App\Models\Setting::updateOrCreate(['key' => 'test_key'], ['value' => 'test_val']);

    $service = new ExportService();
    $treatment = \App\Models\Treatment::withoutGlobalScopes()->first();
    $data = json_decode($service->generate([$treatment->id]), true);

    expect($data['settings'])->toHaveKey('test_key');
});

it('excludes posology history for unselected treatments', function () {
    $treatment = \App\Models\Treatment::withoutGlobalScopes()->first();

    // Insert a history entry for this treatment
    \App\Models\PosologyHistory::create([
        'treatment_id' => $treatment->id,
        'profile_id'   => $treatment->profile_id,
        'dose'         => 500,
        'started_at'   => now(),
    ]);

    $service = new ExportService();

    // Export with empty filter → history included
    $all = json_decode($service->generate(), true);
    expect($all['posology_history'])->not->toBeEmpty();

    // Export with an ID that doesn't match any treatment → history excluded
    $nonExistentId = \App\Models\Treatment::withoutGlobalScopes()->max('id') + 9999;
    $data = json_decode($service->generate([$nonExistentId]), true);
    expect($data['posology_history'])->toBeEmpty();
    expect($data['treatments'])->toBeEmpty();
});

it('excludes calendar events for unselected treatments', function () {
    $treatment = \App\Models\Treatment::withoutGlobalScopes()->first();

    \App\Models\CalendarEvent::create([
        'treatment_id'   => $treatment->id,
        'profile_id'     => $treatment->profile_id,
        'scheduled_date' => now()->toDateString(),
        'is_cancelled'   => false,
    ]);

    $service = new ExportService();

    $all = json_decode($service->generate(), true);
    expect($all['calendar_events'])->not->toBeEmpty();

    $nonExistentId = \App\Models\Treatment::withoutGlobalScopes()->max('id') + 9999;
    $data = json_decode($service->generate([$nonExistentId]), true);
    expect($data['calendar_events'])->toBeEmpty();
});
