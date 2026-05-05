<?php

namespace Tests\Feature;

use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_application_returns_a_successful_response(): void
    {
        Setting::set('onboarding_completed', '1');

        $response = $this->get('/');

        $response->assertStatus(200);
    }
}
