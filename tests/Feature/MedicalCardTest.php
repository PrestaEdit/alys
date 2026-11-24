<?php

use App\Livewire\MedicalCard;
use App\Livewire\Profiles;
use App\Models\Profile;
use App\Models\WeightEntry;
use App\Services\ActiveProfile;
use App\Services\ExportService;
use App\Services\ImportService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    Carbon::setTestNow('2026-11-24 10:00:00');
    $this->profile = Profile::create(['name' => 'Alys', 'color' => '#0ea5e9', 'icon' => 'A']);
    app(ActiveProfile::class)->set($this->profile->id);
});

afterEach(fn () => Carbon::setTestNow());

it('invites to fill the record when empty', function () {
    Livewire::test(MedicalCard::class)
        ->assertSee(__('profiles.medical_fill'));
});

it('shows a compact summary', function () {
    $this->profile->update(['weight_kg' => 72.5, 'height_cm' => 178, 'blood_group' => 'O+']);
    app()->setLocale('fr');

    Livewire::test(MedicalCard::class)
        ->assertSee('72,5 kg · 178 cm · O+');
});

it('opens the sheet prefilled with current values', function () {
    app()->setLocale('fr');
    $this->profile->update(['weight_kg' => 72.5, 'height_cm' => 178, 'blood_group' => 'O+']);

    Livewire::test(MedicalCard::class)
        ->call('openSheet')
        ->assertSet('open', true)
        ->assertSet('weight', '72,5')
        ->assertSet('height', '178')
        ->assertSet('bloodGroup', 'O+');
});

it('steps the weight by 0.1 kg', function () {
    app()->setLocale('fr');
    $this->profile->update(['weight_kg' => 72.5]);

    Livewire::test(MedicalCard::class)
        ->call('openSheet')
        ->call('stepWeight', 0.1)
        ->assertSet('weight', '72,6')
        ->call('stepWeight', -0.1)
        ->call('stepWeight', -0.1)
        ->assertSet('weight', '72,4');
});

it('steps the height by 1 cm within bounds', function () {
    $this->profile->update(['height_cm' => 133]);

    Livewire::test(MedicalCard::class)
        ->call('openSheet')
        ->call('stepHeight', 1)
        ->assertSet('height', '134')
        ->call('stepHeight', -1)
        ->call('stepHeight', -1)
        ->assertSet('height', '132')
        ->set('height', '250')
        ->call('stepHeight', 1)
        ->assertSet('height', '250');
});

it('saves the record and logs the weight in history', function () {
    Livewire::test(MedicalCard::class)
        ->call('openSheet')
        ->set('weight', '71,8')
        ->set('height', '178')
        ->call('toggleBloodGroup', 'A-')
        ->call('save')
        ->assertHasNoErrors()
        ->assertSet('open', false);

    $fresh = $this->profile->fresh();
    expect((float) $fresh->weight_kg)->toBe(71.8)
        ->and($fresh->height_cm)->toBe(178)
        ->and($fresh->blood_group)->toBe('A-');

    $entries = $fresh->weightEntries()->get();
    expect($entries)->toHaveCount(1)
        ->and((float) $entries[0]->weight_kg)->toBe(71.8)
        ->and($entries[0]->measured_on->toDateString())->toBe('2026-11-24');
});

it('keeps one weigh-in per day', function () {
    $card = Livewire::test(MedicalCard::class);
    $card->call('openSheet')->set('weight', '70')->call('save');
    $card->call('openSheet')->set('weight', '70.4')->call('save');

    $entries = $this->profile->weightEntries()->get();
    expect($entries)->toHaveCount(1)
        ->and((float) $entries[0]->weight_kg)->toBe(70.4);
});

it('does not log history when only height changes', function () {
    $this->profile->update(['weight_kg' => 70]);

    Livewire::test(MedicalCard::class)
        ->call('openSheet')->set('height', '180')->call('save');

    expect(WeightEntry::count())->toBe(0);
});

it('shows the delta since the previous weigh-in', function () {
    app()->setLocale('fr');
    WeightEntry::create(['profile_id' => $this->profile->id, 'weight_kg' => 73.0, 'measured_on' => '2026-11-12']);
    $this->profile->update(['weight_kg' => 73.0]);

    Livewire::test(MedicalCard::class)
        ->call('openSheet')->set('weight', '71.8')->call('save')
        ->assertSee('−1,2 kg depuis le 12 nov.');
});

it('rejects an out of range weight', function () {
    Livewire::test(MedicalCard::class)
        ->call('openSheet')->set('weight', '900')->call('save')
        ->assertHasErrors(['weight'])
        ->assertSet('open', true);
});

it('also logs history when editing from the profiles screen', function () {
    Livewire::test(Profiles::class)
        ->call('startEdit', $this->profile->id)
        ->set('editWeight', '64.2')
        ->call('saveEdit')
        ->assertHasNoErrors();

    expect((float) $this->profile->weightEntries()->first()->weight_kg)->toBe(64.2);
});

it('round-trips the record and history through export and import', function () {
    $this->profile->update(['height_cm' => 178, 'blood_group' => 'B+']);
    $this->profile->updateMedical(72.5, 178, 'B+');
    WeightEntry::create(['profile_id' => $this->profile->id, 'weight_kg' => 74.0, 'measured_on' => '2026-10-01']);

    $data = json_decode((new ExportService())->generate(), true);
    expect($data['weight_entries'])->toHaveCount(2);

    WeightEntry::query()->delete();
    $this->profile->update(['weight_kg' => null, 'height_cm' => null, 'blood_group' => null]);

    app(ImportService::class)->restoreFromData($data);

    $fresh = $this->profile->fresh();
    expect((float) $fresh->weight_kg)->toBe(72.5)
        ->and($fresh->height_cm)->toBe(178)
        ->and($fresh->blood_group)->toBe('B+')
        ->and($fresh->weightEntries()->count())->toBe(2);
});

it('keeps local medical data when importing an older export', function () {
    $this->profile->update(['weight_kg' => 70, 'blood_group' => 'O-']);

    app(ImportService::class)->restoreFromData([
        'profiles' => [['id' => 99, 'name' => 'Alys', 'color' => '#0ea5e9', 'icon' => 'A']],
        'treatments' => [], 'posology_history' => [], 'calendar_events' => [],
    ]);

    $fresh = $this->profile->fresh();
    expect((float) $fresh->weight_kg)->toBe(70.0)
        ->and($fresh->blood_group)->toBe('O-');
});
