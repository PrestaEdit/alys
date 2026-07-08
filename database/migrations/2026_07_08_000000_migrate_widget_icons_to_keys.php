<?php

use App\Support\MedicalIcons;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        foreach (MedicalIcons::EMOJI_TO_KEY as $emoji => $key) {
            DB::table('treatments')
                ->where('widget_icon', $emoji)
                ->update(['widget_icon' => $key]);
        }
    }

    public function down(): void
    {
        // Inverse : clé → emoji. Le mapping est 1:1 par emoji source, safe.
        foreach (MedicalIcons::EMOJI_TO_KEY as $emoji => $key) {
            DB::table('treatments')
                ->where('widget_icon', $key)
                ->update(['widget_icon' => $emoji]);
        }
    }
};
