<?php

use App\Services\ExportService;
use App\Models\Treatment;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(DatabaseSeeder::class);
    $this->service = app(ExportService::class);
});

it('generates valid JSON', function () {
    $json = $this->service->generate();
    $data = json_decode($json, true);
    expect($data)->not->toBeNull();
});

it('export contains all required sections', function () {
    $data = json_decode($this->service->generate(), true);
    expect($data)->toHaveKeys(['settings', 'profiles', 'treatments', 'posology_history', 'calendar_events', 'exported_at']);
});

it('export contains all 8 treatments', function () {
    $data = json_decode($this->service->generate(), true);
    expect(count($data['treatments']))->toBe(8);
});

it('export contains calendar events', function () {
    $data = json_decode($this->service->generate(), true);
    expect(count($data['calendar_events']))->toBeGreaterThan(0);
});

it('export contains settings', function () {
    $data = json_decode($this->service->generate(), true);
    expect($data['settings'])->toHaveKey('treatment_start');
    expect($data['settings'])->toHaveKey('treatment_end');
});
