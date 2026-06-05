<?php

namespace Tests\Feature;

use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SetLocaleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Setting::set('onboarding_completed', '1'); // avoid onboarding redirect
    }

    public function test_unsupported_language_falls_back_to_french(): void
    {
        $this->get('/', ['Accept-Language' => 'de-DE,es;q=0.9'])->assertOk();
        $this->assertSame('fr', app()->getLocale());
    }

    public function test_detects_english_from_accept_language(): void
    {
        $this->get('/', ['Accept-Language' => 'en-US,en;q=0.9']);
        $this->assertSame('en', app()->getLocale());
    }

    public function test_french_accept_language_stays_french(): void
    {
        $this->get('/', ['Accept-Language' => 'fr-FR,fr;q=0.9']);
        $this->assertSame('fr', app()->getLocale());
    }

    public function test_setting_overrides_accept_language(): void
    {
        Setting::set('locale', 'en');
        $this->get('/', ['Accept-Language' => 'fr-FR']);
        $this->assertSame('en', app()->getLocale());
    }

    public function test_invalid_setting_falls_back_to_detection(): void
    {
        Setting::set('locale', 'de');
        $this->get('/', ['Accept-Language' => 'en-US']);
        $this->assertSame('en', app()->getLocale());
    }
}
