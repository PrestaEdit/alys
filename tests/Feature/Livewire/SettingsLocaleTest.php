<?php

namespace Tests\Feature\Livewire;

use App\Livewire\Settings;
use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SettingsLocaleTest extends TestCase
{
    use RefreshDatabase;

    public function test_set_locale_persists_choice(): void
    {
        Livewire::test(Settings::class)
            ->call('setLocale', 'en');

        $this->assertSame('en', Setting::get('locale'));
    }

    public function test_set_locale_rejects_unsupported(): void
    {
        Livewire::test(Settings::class)
            ->call('setLocale', 'de');

        $this->assertSame('', Setting::get('locale', ''));
    }
}
