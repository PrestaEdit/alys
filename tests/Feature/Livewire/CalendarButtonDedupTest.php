<?php

use App\Livewire\Calendar;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(fn() => $this->seed(DatabaseSeeder::class));

it('n\'affiche qu\'un seul bouton d\'ajout d\'événement sur Calendar', function () {
    $html = Livewire::test(Calendar::class)->html();

    $count = substr_count($html, 'wire:click="openEventModal"');
    expect($count)->toBe(1);
});

it('le bouton d\'ajout du panneau utilise un fond bleu plein', function () {
    $html = Livewire::test(Calendar::class)->html();

    // Ancre l'assertion sur le bouton lui-même : le bg-sky-500 doit être dans
    // les classes du bouton wire:click="openEventModal", pas ailleurs dans la
    // page (cellule d'aujourd'hui, boutons de modales, etc.).
    // Le bouton a `text-white` puis `bg-sky-500` (dans cet ordre) dans ses classes.
    // `[\s\S]` autorise le retour à la ligne entre wire:click et class="...".
    expect($html)->toMatch('/wire:click="openEventModal"[\s\S]*?class="[^"]*text-white[^"]*bg-sky-500/');
});
