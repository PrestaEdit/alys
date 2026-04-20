<?php

namespace Database\Seeders;

use App\Models\Setting;
use App\Models\Treatment;
use App\Models\PosologyHistory;
use Illuminate\Database\Seeder;

class TreatmentSeeder extends Seeder
{
    public function run(): void
    {
        Setting::set('treatment_start', '2025-11-26');
        Setting::set('treatment_end', '2027-03-31');

        $treatments = [
            [
                'name' => '6-MP',
                'commercial_name' => 'Purinéthol',
                'type' => 'daily',
                'unit' => 'cachet',
                'current_dose' => 1.00,
                'color' => '#3b82f6',
                'frequency_weeks' => null,
                'day_of_week' => null,
                'recurrence_start' => null,
                'show_widget' => false,
                'widget_icon' => null,
            ],
            [
                'name' => '6-TG',
                'commercial_name' => 'Lanvis',
                'type' => 'daily',
                'unit' => 'ml',
                'current_dose' => 3.00,
                'color' => '#10b981',
                'frequency_weeks' => null,
                'day_of_week' => null,
                'recurrence_start' => null,
                'show_widget' => false,
                'widget_icon' => null,
            ],
            [
                'name' => 'MTX',
                'commercial_name' => 'Méthotrexate',
                'type' => 'weekly',
                'unit' => 'cachet',
                'current_dose' => 9.00,
                'color' => '#ef4444',
                'frequency_weeks' => null,
                'day_of_week' => 1, // mardi (0=lun)
                'recurrence_start' => null,
                'show_widget' => true,
                'widget_icon' => '💊',
            ],
            [
                'name' => 'VCR',
                'commercial_name' => 'Vincristine',
                'type' => 'cyclic',
                'unit' => 'IV',
                'current_dose' => null,
                'color' => '#8b5cf6',
                'frequency_weeks' => 4,
                'day_of_week' => null,
                'recurrence_start' => '2025-11-26',
                'show_widget' => true,
                'widget_icon' => '💉',
            ],
            [
                'name' => 'IT MTTX',
                'commercial_name' => 'Ponction lombaire',
                'type' => 'cyclic',
                'unit' => null,
                'current_dose' => null,
                'color' => '#0ea5e9',
                'frequency_weeks' => 12,
                'is_medical_act' => true,
                'requires_fasting' => true,
                'day_of_week' => null,
                'recurrence_start' => '2026-01-21',
                'show_widget' => true,
                'widget_icon' => '🔬',
            ],
            [
                'name' => 'Dexaméthasone',
                'commercial_name' => 'Dexaméthasone',
                'type' => 'cyclic',
                'unit' => 'cachet',
                'current_dose' => 1.00,
                'color' => '#ec4899',
                'frequency_weeks' => null,
                'day_of_week' => null,
                'recurrence_start' => null,
                'show_widget' => false,
                'widget_icon' => null,
            ],
            [
                'name' => 'Aérosol',
                'commercial_name' => 'Aérosol',
                'type' => 'cyclic',
                'unit' => null,
                'current_dose' => null,
                'color' => '#f59e0b',
                'frequency_weeks' => 4,
                'day_of_week' => null,
                'recurrence_start' => '2025-11-26',
                'show_widget' => false,
                'widget_icon' => null,
            ],
            [
                'name' => 'Hôpital',
                'commercial_name' => 'Visite hôpital',
                'type' => 'cyclic',
                'unit' => null,
                'current_dose' => null,
                'color' => '#f97316',
                'frequency_weeks' => 2,
                'day_of_week' => null,
                'recurrence_start' => '2025-11-26',
                'show_widget' => true,
                'widget_icon' => '🏥',
            ],
        ];

        foreach ($treatments as $data) {
            Treatment::create($data);
        }

        // Posologie initiale 6-TG : 2,8 ml depuis le 26/11/2025
        $sixTg = Treatment::where('name', '6-TG')->first();
        PosologyHistory::create([
            'treatment_id' => $sixTg->id,
            'dose' => 2.80,
            'note' => 'Posologie initiale',
            'started_at' => '2025-11-26',
        ]);
        PosologyHistory::create([
            'treatment_id' => $sixTg->id,
            'dose' => 3.00,
            'note' => 'Passage à 3ml suite RDV',
            'started_at' => '2026-04-15',
        ]);
    }
}
