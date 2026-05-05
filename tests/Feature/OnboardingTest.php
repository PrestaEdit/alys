<?php

namespace Tests\Feature;

use App\Livewire\Onboarding;
use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class OnboardingTest extends TestCase
{
    use RefreshDatabase;

    public function test_route_returns_ok(): void
    {
        $this->get('/onboarding')->assertOk();
    }

    public function test_step_1_requires_patient_name(): void
    {
        Livewire::test(Onboarding::class)
            ->set('patientName', '')
            ->call('nextStep')
            ->assertHasErrors(['patientName' => 'required']);
    }

    public function test_step_2_requires_end_after_start(): void
    {
        Livewire::test(Onboarding::class)
            ->set('patientName', 'Alys')
            ->call('nextStep')
            ->set('treatmentStart', '2026-12-01')
            ->set('treatmentEnd', '2026-11-01')
            ->call('nextStep')
            ->assertHasErrors(['treatmentEnd']);
    }

    public function test_complete_persists_settings_and_redirects_home(): void
    {
        Livewire::test(Onboarding::class)
            ->set('patientName', 'Alys')
            ->call('nextStep')
            ->set('treatmentStart', '2026-01-01')
            ->set('treatmentEnd', '2026-12-31')
            ->call('nextStep')
            ->call('complete')
            ->assertRedirect('/');

        $this->assertSame('Alys', Setting::get('patient_name'));
        $this->assertSame('2026-01-01', Setting::get('treatment_start'));
        $this->assertSame('2026-12-31', Setting::get('treatment_end'));
        $this->assertSame('1', Setting::get('onboarding_completed'));
    }

    public function test_complete_and_add_treatment_redirects_to_treatment_create(): void
    {
        Livewire::test(Onboarding::class)
            ->set('patientName', 'Alys')
            ->call('nextStep')
            ->set('treatmentStart', '2026-01-01')
            ->set('treatmentEnd', '2026-12-31')
            ->call('nextStep')
            ->call('completeAndAddTreatment')
            ->assertRedirect('/treatments/create');

        $this->assertSame('1', Setting::get('onboarding_completed'));
    }
}
