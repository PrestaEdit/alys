<?php

namespace Database\Seeders;

use App\Models\Profile;
use App\Services\ActiveProfile;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $profile = Profile::firstOrCreate(
            ['name' => 'Alys'],
            [
                'color' => '#0ea5e9',
                'icon' => 'A',
                'treatment_start' => '2025-11-26',
                'treatment_end' => '2027-03-31',
            ]
        );
        app(ActiveProfile::class)->set($profile->id);

        $this->call([
            TreatmentSeeder::class,
            CalendarSeeder::class,
        ]);
    }
}
