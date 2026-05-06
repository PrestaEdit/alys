<?php

namespace Tests\Feature;

use App\Models\Profile;
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

    public function test_flag_is_set_when_profile_exists_and_flag_missing(): void
    {
        Profile::create(['name' => 'Test', 'treatment_start' => '2026-01-01', 'treatment_end' => '2026-12-31']);

        $this->invokeBootstrap();

        $this->assertSame('1', Setting::get('onboarding_completed'));
    }

    public function test_flag_is_not_overwritten_if_already_set(): void
    {
        Profile::create(['name' => 'Test', 'treatment_start' => '2026-01-01', 'treatment_end' => '2026-12-31']);
        Setting::set('onboarding_completed', '1');

        $this->invokeBootstrap();

        $this->assertSame('1', Setting::get('onboarding_completed'));
    }

    public function test_flag_is_not_set_when_no_profile_exists(): void
    {
        $this->invokeBootstrap();

        $this->assertSame('', Setting::get('onboarding_completed', ''));
    }
}
