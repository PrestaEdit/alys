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

    expect($html)->toContain('bg-sky-500');
    expect($html)->toContain('text-white');
});
