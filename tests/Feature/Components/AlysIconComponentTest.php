<?php

use App\View\Components\AlysIcon;
use Illuminate\Support\Facades\Blade;

beforeEach(fn() => AlysIcon::flushCache());

// Les 4 assets ci-dessous sont versionnés dans public/icons/. Si l'un manque,
// le composant tombe silencieusement sur le fallback SVG — un skip masquerait
// la régression. On casse fort si les assets ne sont plus là.
beforeEach(function () {
    $required = [
        'icons/medical/pill.svg',
        'icons/medical/syringe.svg',
        'icons/twemoji/1f382.svg',
        'icons/twemoji/2708.svg',
    ];
    foreach ($required as $rel) {
        if (! file_exists(public_path($rel))) {
            throw new RuntimeException("Asset requis absent : {$rel}");
        }
    }
});

it('rend un SVG médical connu quand il existe', function () {
    $rendered = Blade::render('<x-alys-icon value="pill" />');
    expect($rendered)->toContain('<svg');
});

it('rend un SVG Twemoji pour un emoji connu', function () {
    // 🎂 = 1f382.svg
    $rendered = Blade::render('<x-alys-icon value="🎂" />');
    expect($rendered)->toContain('<svg');
});

it('rend un SVG fallback si le fichier est introuvable', function () {
    $rendered = Blade::render('<x-alys-icon value="unknown-key-xyz-zzz" />');
    expect($rendered)->toContain('<svg');
    expect($rendered)->toContain('alys-icon-fallback');
});

it('accepte kind="medical" explicite (bypass auto-detect)', function () {
    $rendered = Blade::render('<x-alys-icon value="syringe" kind="medical" />');
    expect($rendered)->toContain('<svg');
});

it('convertit correctement un emoji multi-codepoint (sans fe0f)', function () {
    // ✈️ = U+2708 U+FE0F, filename Twemoji = 2708.svg (sans fe0f)
    $rendered = Blade::render('<x-alys-icon value="✈️" />');
    expect($rendered)->toContain('<svg');
    expect($rendered)->not->toContain('alys-icon-fallback');
});

it('rend la même icône à deux tailles différentes sans collision de cache', function () {
    $small = Blade::render('<x-alys-icon value="pill" class="w-6 h-6" />');
    $large = Blade::render('<x-alys-icon value="pill" class="w-10 h-10" />');

    // Chaque rendu doit contenir SA taille — pas celle de l'autre.
    expect($small)->toContain('w-6 h-6');
    expect($large)->toContain('w-10 h-10');
    expect($small)->not->toContain('w-10 h-10');
    expect($large)->not->toContain('w-6 h-6');
});

it('préserve un attribut class= déjà présent sur la balise <svg> racine', function () {
    // On simule un SVG local qui a déjà sa propre class= — le composant ne doit
    // pas dupliquer ni écraser.
    $tempDir = public_path('icons/medical');
    $tempFile = $tempDir . '/__test-preserve-class.svg';
    file_put_contents($tempFile, '<svg class="preexisting-class" viewBox="0 0 24 24"></svg>');

    try {
        // On ne peut pas utiliser directement value=... car pas dans MEDICAL_KEYS.
        // On passe par kind=medical explicite.
        AlysIcon::flushCache();
        $rendered = Blade::render(
            '<x-alys-icon value="__test-preserve-class" kind="medical" class="new-class" />'
        );

        expect($rendered)->toContain('preexisting-class');
        expect($rendered)->not->toContain('new-class');
    } finally {
        @unlink($tempFile);
    }
});
