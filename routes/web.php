<?php

use App\Livewire\Dashboard;
use App\Livewire\Calendar;
use App\Livewire\Onboarding;
use App\Livewire\Treatments;
use App\Livewire\TreatmentEdit;
use App\Livewire\TreatmentCreate;
use App\Livewire\Settings;
use Illuminate\Support\Facades\Route;

Route::get('/', Dashboard::class)->name('home');
Route::get('/calendar', Calendar::class)->name('calendar');
Route::get('/treatments', Treatments::class)->name('treatments');
Route::get('/treatments/create', TreatmentCreate::class)->name('treatments.create');
Route::get('/treatments/{treatment}/edit', TreatmentEdit::class)->name('treatments.edit');
Route::get('/settings', Settings::class)->name('settings');
Route::get('/onboarding', Onboarding::class)->name('onboarding');
