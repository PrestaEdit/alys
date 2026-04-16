<?php

use App\Livewire\Dashboard;
use App\Livewire\Calendar;
use App\Livewire\Treatments;
use App\Livewire\TreatmentEdit;
use Illuminate\Support\Facades\Route;

Route::get('/', Dashboard::class)->name('home');
Route::get('/calendar', Calendar::class)->name('calendar');
Route::get('/treatments', Treatments::class)->name('treatments');
Route::get('/treatments/{treatment}/edit', TreatmentEdit::class)->name('treatments.edit');
