<?php

use App\Livewire\Treatments;
use App\Livewire\TreatmentEdit;
use App\Models\PosologyHistory;
use App\Models\Treatment;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(fn() => $this->seed(DatabaseSeeder::class));

it('renders treatments list', function () {
    Livewire::test(Treatments::class)->assertStatus(200);
});

it('increments dose by 0.5 for tablet unit', function () {
    $sixMp = Treatment::where('name', '6-MP')->first();
    $component = Livewire::test(TreatmentEdit::class, ['treatment' => $sixMp]);
    $before = $component->get('newDose');
    $component->call('increment');
    expect($component->get('newDose'))->toBe($before + 0.5);
});

it('increments dose by 0.1 for ml unit', function () {
    $sixTg = Treatment::where('name', '6-TG')->first();
    $component = Livewire::test(TreatmentEdit::class, ['treatment' => $sixTg]);
    $before = $component->get('newDose');
    $component->call('increment');
    expect(round($component->get('newDose'), 1))->toBe(round($before + 0.1, 1));
});

it('saves new dose and creates posology history entry', function () {
    $sixMp = Treatment::where('name', '6-MP')->first();
    $countBefore = PosologyHistory::where('treatment_id', $sixMp->id)->count();

    Livewire::test(TreatmentEdit::class, ['treatment' => $sixMp])
        ->set('newDose', 2.0)
        ->set('note', 'Augmentation de dose')
        ->call('save');

    expect(PosologyHistory::where('treatment_id', $sixMp->id)->count())->toBe($countBefore + 1);
    $sixMp->refresh();
    expect((float) $sixMp->current_dose)->toBe(2.0);
});

it('does not go below 0 on decrement', function () {
    $sixMp = Treatment::where('name', '6-MP')->first();
    $component = Livewire::test(TreatmentEdit::class, ['treatment' => $sixMp])
        ->set('newDose', 0.0)
        ->call('decrement');
    expect($component->get('newDose'))->toBe(0.0);
});

it('IT MTTX treatment is not dosage editable', function () {
    $itMttx = Treatment::where('name', 'IT MTTX')->first();
    expect($itMttx->isDosageEditable())->toBeFalse();

    Livewire::test(Treatments::class)
        ->assertSee('Modifier'); // sanity: at least one editable treatment shows the button
});
