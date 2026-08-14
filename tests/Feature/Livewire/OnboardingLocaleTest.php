<?php

namespace Tests\Feature\Livewire;

use App\Livewire\Onboarding;
use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class OnboardingLocaleTest extends TestCase
{
    use RefreshDatabase;

    public function test_set_locale_persists_choice(): void
    {
        Livewire::test(Onboarding::class)
            ->call('setLocale', 'en');

        $this->assertSame('en', Setting::get('locale'));
        $this->assertSame('en', app()->getLocale());
    }

    public function test_set_locale_rejects_unsupported(): void
    {
        Livewire::test(Onboarding::class)
            ->call('setLocale', 'de');

        $this->assertSame('', Setting::get('locale', ''));
    }

    public function test_set_locale_preserves_current_step(): void
    {
        Livewire::test(Onboarding::class)
            ->set('step', 3)
            ->call('setLocale', 'en')
            ->assertSet('step', 3);
    }

    public function test_language_pills_render_on_first_load(): void
    {
        Livewire::test(Onboarding::class)
            ->assertSeeHtml("wire:click=\"setLocale('fr')\"")
            ->assertSeeHtml("wire:click=\"setLocale('en')\"");
    }
}
