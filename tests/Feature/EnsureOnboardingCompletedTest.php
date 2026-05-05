<?php

namespace Tests\Feature;

use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EnsureOnboardingCompletedTest extends TestCase
{
    use RefreshDatabase;

    public function test_redirects_to_onboarding_when_flag_missing(): void
    {
        $this->get('/')->assertRedirect('/onboarding');
    }

    public function test_allows_onboarding_route_when_flag_missing(): void
    {
        $this->get('/onboarding')->assertOk();
    }

    public function test_allows_home_when_flag_set(): void
    {
        Setting::set('onboarding_completed', '1');
        $this->get('/')->assertOk();
    }
}
