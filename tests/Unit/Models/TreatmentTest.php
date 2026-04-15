<?php

use App\Models\Treatment;
use App\Models\CalendarEvent;

it('isDaily returns true for daily treatments', function () {
    $treatment = new Treatment(['type' => 'daily']);
    expect($treatment->isDaily())->toBeTrue();
});

it('isDaily returns false for non-daily treatments', function () {
    $treatment = new Treatment(['type' => 'weekly']);
    expect($treatment->isDaily())->toBeFalse();
});

it('isDosageEditable returns false for medical_act', function () {
    $treatment = new Treatment(['type' => 'medical_act']);
    expect($treatment->isDosageEditable())->toBeFalse();
});

it('isDosageEditable returns true for daily treatments', function () {
    $treatment = new Treatment(['type' => 'daily']);
    expect($treatment->isDosageEditable())->toBeTrue();
});

it('hasMoved returns true when original_date is set', function () {
    $event = new CalendarEvent(['original_date' => now()->toDateString()]);
    expect($event->hasMoved())->toBeTrue();
});
