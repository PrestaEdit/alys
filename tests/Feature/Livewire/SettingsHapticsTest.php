<?php

namespace Tests\Feature\Livewire;

use App\Livewire\Settings;
use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SettingsHapticsTest extends TestCase
{
    use RefreshDatabase;

    public function test_haptics_default_is_enabled(): void
    {
        // Rien de stocké → activé par défaut (via la valeur par défaut du get).
        $this->assertSame('1', Setting::get('haptics_enabled', '1'));
    }

    public function test_toggle_haptics_disables_when_enabled(): void
    {
        Setting::set('haptics_enabled', '1');

        Livewire::test(Settings::class)
            ->call('toggleHaptics')
            ->assertDispatched('haptics-changed', enabled: false);

        $this->assertSame('0', Setting::get('haptics_enabled'));
    }

    public function test_toggle_haptics_enables_when_disabled(): void
    {
        Setting::set('haptics_enabled', '0');

        Livewire::test(Settings::class)
            ->call('toggleHaptics')
            ->assertDispatched('haptics-changed', enabled: true);

        $this->assertSame('1', Setting::get('haptics_enabled'));
    }

    public function test_toggle_haptics_from_default_treats_as_enabled(): void
    {
        // Aucun setting stocké → considéré actif → premier toggle désactive.
        Livewire::test(Settings::class)
            ->call('toggleHaptics')
            ->assertDispatched('haptics-changed', enabled: false);

        $this->assertSame('0', Setting::get('haptics_enabled'));
    }
}
