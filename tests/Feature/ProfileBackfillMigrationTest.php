<?php

namespace Tests\Feature;

use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ProfileBackfillMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_backfill_creates_default_profile_and_assigns_rows(): void
    {
        // Rollback de la contrainte NOT NULL pour pouvoir simuler l'état pré-migration.
        $notNullMigration = include database_path('migrations/2026_05_06_000006_make_profile_id_required.php');
        $notNullMigration->down();

        // Reset état
        Schema::disableForeignKeyConstraints();
        DB::table('treatments')->delete();
        DB::table('calendar_events')->delete();
        DB::table('posology_history')->delete();
        DB::table('profiles')->delete();
        DB::table('settings')->delete();
        Schema::enableForeignKeyConstraints();

        DB::table('settings')->insert([
            ['key' => 'patient_name', 'value' => 'Camille'],
            ['key' => 'treatment_start', 'value' => '2026-02-01'],
            ['key' => 'treatment_end', 'value' => '2027-06-30'],
        ]);

        $now = now();
        DB::table('treatments')->insert([
            'name' => 'Doliprane',
            'type' => 'daily',
            'profile_id' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        // Exécution du back-fill
        $migration = include database_path('migrations/2026_05_06_000005_backfill_profiles_data.php');
        $migration->up();

        $profile = DB::table('profiles')->first();
        $this->assertNotNull($profile);
        $this->assertSame('Camille', $profile->name);
        $this->assertSame('C', $profile->icon);
        $this->assertSame((string) $profile->id, Setting::get('active_profile_id'));

        $this->assertSame($profile->id, DB::table('treatments')->where('name', 'Doliprane')->value('profile_id'));
        $this->assertSame('', Setting::get('patient_name', ''));
    }

    public function test_backfill_no_op_when_no_treatments(): void
    {
        Schema::disableForeignKeyConstraints();
        DB::table('profiles')->delete();
        DB::table('treatments')->delete();
        DB::table('calendar_events')->delete();
        DB::table('posology_history')->delete();
        Schema::enableForeignKeyConstraints();

        $migration = include database_path('migrations/2026_05_06_000005_backfill_profiles_data.php');
        $migration->up();

        $this->assertSame(0, DB::table('profiles')->count());
    }
}
