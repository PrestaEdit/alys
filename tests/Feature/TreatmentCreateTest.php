<?php

use App\Livewire\TreatmentCreate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('démarre à l\'étape 1', function () {
    Livewire::test(TreatmentCreate::class)
        ->assertSet('step', 1);
});

it('nextStep valide le nom avant d\'avancer', function () {
    Livewire::test(TreatmentCreate::class)
        ->call('nextStep')
        ->assertHasErrors(['name']);
});

it('nextStep avance à l\'étape 2 si étape 1 valide', function () {
    Livewire::test(TreatmentCreate::class)
        ->set('name', 'Méthotrexate')
        ->set('type', 'daily')
        ->set('color', '#3b82f6')
        ->call('nextStep')
        ->assertSet('step', 2);
});

it('applicableSteps exclut posologie si acte médical', function () {
    $component = Livewire::test(TreatmentCreate::class)
        ->set('isMedicalAct', true);

    expect($component->instance()->applicableSteps())->not->toContain(3);
});

it('applicableSteps exclut récurrence si type non cyclique', function () {
    $component = Livewire::test(TreatmentCreate::class)
        ->set('type', 'daily');

    expect($component->instance()->applicableSteps())->not->toContain(4);
});

it('applicableSteps inclut toutes les étapes si cyclique non-médical', function () {
    $component = Livewire::test(TreatmentCreate::class)
        ->set('type', 'cyclic')
        ->set('isMedicalAct', false);

    expect($component->instance()->applicableSteps())->toBe([1, 2, 3, 4, 5]);
});

it('nextStep saute l\'étape 3 si acte médical', function () {
    Livewire::test(TreatmentCreate::class)
        ->set('name', 'Acte test')
        ->set('type', 'daily')
        ->set('color', '#3b82f6')
        ->set('isMedicalAct', true)
        ->call('nextStep') // 1 → 2
        ->assertSet('step', 2)
        ->call('nextStep') // 2 → 5 (3 et 4 skippés)
        ->assertSet('step', 5);
});

it('prevStep revient en arrière', function () {
    Livewire::test(TreatmentCreate::class)
        ->set('name', 'Test')
        ->set('type', 'daily')
        ->set('color', '#3b82f6')
        ->call('nextStep')
        ->assertSet('step', 2)
        ->call('prevStep')
        ->assertSet('step', 1);
});

it('stepLabel retourne le bon titre', function () {
    $component = Livewire::test(TreatmentCreate::class);
    expect($component->instance()->stepLabel())->toBe('Informations de base');
});
