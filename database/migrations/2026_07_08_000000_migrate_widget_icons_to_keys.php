<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Mapping figé au moment de cette migration.
     *
     * NE PAS référencer App\Support\MedicalIcons::EMOJI_TO_KEY ici : les
     * migrations doivent être auto-suffisantes et rester déterministes même si
     * ce mapping évolue plus tard (ajouts, renommages). Un `migrate:fresh` sur
     * un backup ancien doit reproduire l'état exact de cette date.
     */
    private const MAPPING = [
        '💊' => 'pill',
        '💉' => 'syringe',
        '🩺' => 'stethoscope',
        '🧪' => 'test-tube',
        '🩸' => 'blood-drop',
        '🏥' => 'hospital',
        '🧬' => 'dna',
        '🔬' => 'microscope',
        '🩹' => 'bandage',
    ];

    public function up(): void
    {
        DB::transaction(function () {
            foreach (self::MAPPING as $emoji => $key) {
                DB::table('treatments')
                    ->where('widget_icon', $emoji)
                    ->update(['widget_icon' => $key]);
            }
        });
    }

    public function down(): void
    {
        // Inverse : clé → emoji. Le mapping est 1:1 par emoji source, safe.
        DB::transaction(function () {
            foreach (self::MAPPING as $emoji => $key) {
                DB::table('treatments')
                    ->where('widget_icon', $key)
                    ->update(['widget_icon' => $emoji]);
            }
        });
    }
};
