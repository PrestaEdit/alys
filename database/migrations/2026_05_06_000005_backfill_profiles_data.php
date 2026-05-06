<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        $hasProfile = DB::table('profiles')->exists();
        $hasTreatments = DB::table('treatments')->exists();

        if ($hasProfile || ! $hasTreatments) {
            return;
        }

        $name = DB::table('settings')->where('key', 'patient_name')->value('value') ?: 'Alys';
        $start = DB::table('settings')->where('key', 'treatment_start')->value('value') ?: '2026-01-01';
        $end = DB::table('settings')->where('key', 'treatment_end')->value('value') ?: '2027-12-31';

        $now = now();

        $profileId = DB::table('profiles')->insertGetId([
            'name' => $name,
            'color' => '#0ea5e9',
            'icon' => mb_strtoupper(mb_substr($name, 0, 1)) ?: 'A',
            'treatment_start' => $start,
            'treatment_end' => $end,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('treatments')->whereNull('profile_id')->update(['profile_id' => $profileId]);
        DB::table('calendar_events')->whereNull('profile_id')->update(['profile_id' => $profileId]);
        DB::table('posology_history')->whereNull('profile_id')->update(['profile_id' => $profileId]);

        DB::table('settings')->where('key', 'active_profile_id')->delete();
        DB::table('settings')->insert([
            'key' => 'active_profile_id',
            'value' => (string) $profileId,
        ]);

        DB::table('settings')->whereIn('key', ['patient_name', 'treatment_start', 'treatment_end'])->delete();
    }

    public function down(): void
    {
        // Pas de rollback : on laisse la migration suivante gérer.
    }
};
