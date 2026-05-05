<?php

use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    Setting::set('onboarding_completed', '1');
});

it('home route returns 200', function () {
    $response = $this->get(route('home'));
    $response->assertStatus(200);
});

it('calendar route returns 200', function () {
    $response = $this->get(route('calendar'));
    $response->assertStatus(200);
});

it('treatments route returns 200', function () {
    $response = $this->get(route('treatments'));
    $response->assertStatus(200);
});
