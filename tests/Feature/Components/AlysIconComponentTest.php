<?php

use Illuminate\Support\Facades\Blade;

it('rend un SVG médical connu quand il existe', function () {
    if (! file_exists(public_path('icons/medical/pill.svg'))) {
        $this->markTestSkipped('SVG pill absent');
    }
    $rendered = Blade::render('<x-alys-icon value="pill" />');
    expect($rendered)->toContain('<svg');
});

it('rend un SVG Twemoji pour un emoji connu', function () {
    // 🎂 = 1f382.svg
    if (! file_exists(public_path('icons/twemoji/1f382.svg'))) {
        $this->markTestSkipped('SVG twemoji 1f382 absent');
    }
    $rendered = Blade::render('<x-alys-icon value="🎂" />');
    expect($rendered)->toContain('<svg');
});

it('rend un SVG fallback si le fichier est introuvable', function () {
    $rendered = Blade::render('<x-alys-icon value="unknown-key-xyz-zzz" />');
    expect($rendered)->toContain('<svg');
    expect($rendered)->toContain('alys-icon-fallback');
});

it('accepte kind="medical" explicite (bypass auto-detect)', function () {
    if (! file_exists(public_path('icons/medical/syringe.svg'))) {
        $this->markTestSkipped('SVG syringe absent');
    }
    $rendered = Blade::render('<x-alys-icon value="syringe" kind="medical" />');
    expect($rendered)->toContain('<svg');
});

it('convertit correctement un emoji multi-codepoint (sans fe0f)', function () {
    // ✈️ = U+2708 U+FE0F, filename Twemoji = 2708.svg (sans fe0f)
    if (! file_exists(public_path('icons/twemoji/2708.svg'))) {
        $this->markTestSkipped('SVG twemoji 2708 absent');
    }
    $rendered = Blade::render('<x-alys-icon value="✈️" />');
    expect($rendered)->toContain('<svg');
    expect($rendered)->not->toContain('alys-icon-fallback');
});
