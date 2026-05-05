<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Providers\AppServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OnboardingFlagBootstrapTest extends TestCase
{
    use RefreshDatabase;

    private function invokeBootstrap(): void
    {
        $provider = $this->app->getProvider(AppServiceProvider::class);
        $reflection = new \ReflectionClass(AppServiceProvider::class);
        $method = $reflection->getMethod('bootstrapOnboardingFlag');
        $method->setAccessible(true);
        $method->invoke($provider);
    }

    public function test_flag_is_set_when_patient_name_exists_and_flag_missing(): void
    {
        Setting::set('patient_name', 'Alys');

        $this->invokeBootstrap();

        $this->assertSame('1', Setting::get('onboarding_completed'));
    }

    public function test_flag_is_not_overwritten_if_already_set(): void
    {
        Setting::set('patient_name', 'Alys');
        Setting::set('onboarding_completed', '1');

        $this->invokeBootstrap();

        $this->assertSame('1', Setting::get('onboarding_completed'));
    }

    public function test_flag_is_not_set_when_patient_name_missing(): void
    {
        $this->invokeBootstrap();

        $this->assertSame('', Setting::get('onboarding_completed', ''));
    }
}
