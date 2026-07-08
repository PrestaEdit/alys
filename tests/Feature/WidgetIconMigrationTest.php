<?php

use App\Models\Profile;
use App\Models\Treatment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

beforeEach(fn() => $this->seed(\Database\Seeders\DatabaseSeeder::class));

it('mappe les 9 emojis médicaux connus vers leur clé', function () {
    // Purge les données seed et injecte notre fixture pré-migration
    DB::table('calendar_events')->delete();
    DB::table('treatments')->delete();

    $profile = Profile::first() ?? Profile::factory()->create();

    $fixtures = [
        ['name' => 'Trt pill',        'widget_icon' => '💊'],
        ['name' => 'Trt syringe',     'widget_icon' => '💉'],
        ['name' => 'Trt stethoscope', 'widget_icon' => '🩺'],
        ['name' => 'Trt test-tube',   'widget_icon' => '🧪'],
        ['name' => 'Trt blood-drop',  'widget_icon' => '🩸'],
        ['name' => 'Trt hospital',    'widget_icon' => '🏥'],
        ['name' => 'Trt dna',         'widget_icon' => '🧬'],
        ['name' => 'Trt microscope',  'widget_icon' => '🔬'],
        ['name' => 'Trt bandage',     'widget_icon' => '🩹'],
        ['name' => 'Trt heart',       'widget_icon' => '❤️'],  // non mappé — doit rester
    ];

    foreach ($fixtures as $f) {
        DB::table('treatments')->insert([
            'profile_id'  => $profile->id,
            'name'        => $f['name'],
            'type'        => 'daily',
            'color'       => '#000',
            'widget_icon' => $f['widget_icon'],
            'show_widget' => true,
            'created_at'  => now(),
            'updated_at'  => now(),
        ]);
    }

    // Charger le fichier de migration et invoquer up()
    $migration = require database_path('migrations/2026_07_08_000000_migrate_widget_icons_to_keys.php');
    $migration->up();

    $expected = [
        'Trt pill'        => 'pill',
        'Trt syringe'     => 'syringe',
        'Trt stethoscope' => 'stethoscope',
        'Trt test-tube'   => 'test-tube',
        'Trt blood-drop'  => 'blood-drop',
        'Trt hospital'    => 'hospital',
        'Trt dna'         => 'dna',
        'Trt microscope'  => 'microscope',
        'Trt bandage'     => 'bandage',
        'Trt heart'       => '❤️', // non mappé
    ];

    foreach ($expected as $name => $expectedIcon) {
        $actual = DB::table('treatments')->where('name', $name)->value('widget_icon');
        expect($actual)->toBe($expectedIcon, "Traitement $name : attendu '$expectedIcon' mais reçu '$actual'");
    }
});

it('up() est idempotent (re-lancer ne casse rien)', function () {
    DB::table('treatments')->delete();
    $profile = Profile::first() ?? Profile::factory()->create();

    DB::table('treatments')->insert([
        'profile_id' => $profile->id,
        'name'       => 'Trt idempotent',
        'type'       => 'daily',
        'color'      => '#000',
        'widget_icon' => 'pill', // déjà migré
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $migration = require database_path('migrations/2026_07_08_000000_migrate_widget_icons_to_keys.php');
    $migration->up();
    $migration->up(); // deuxième passe

    expect(DB::table('treatments')->where('name', 'Trt idempotent')->value('widget_icon'))->toBe('pill');
});

it('down() ré-inverse le mapping', function () {
    DB::table('treatments')->delete();
    $profile = Profile::first() ?? Profile::factory()->create();

    DB::table('treatments')->insert([
        'profile_id' => $profile->id,
        'name'       => 'Trt reverse',
        'type'       => 'daily',
        'color'      => '#000',
        'widget_icon' => 'pill',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $migration = require database_path('migrations/2026_07_08_000000_migrate_widget_icons_to_keys.php');
    $migration->down();

    expect(DB::table('treatments')->where('name', 'Trt reverse')->value('widget_icon'))->toBe('💊');
});
