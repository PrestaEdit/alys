<?php

namespace Tests\Feature;

use App\Livewire\ProfileSwitcher;
use App\Models\Profile;
use App\Services\ActiveProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ProfileSwitcherTest extends TestCase
{
    use RefreshDatabase;

    public function test_renders_active_profile_icon(): void
    {
        $p = Profile::create(['name' => 'Alys', 'color' => '#0ea5e9', 'icon' => 'A',
            'treatment_start' => '2026-01-01', 'treatment_end' => '2026-12-31']);
        app(ActiveProfile::class)->set($p->id);

        Livewire::test(ProfileSwitcher::class)->assertSee('A');
    }

    public function test_switch_to_changes_active(): void
    {
        $a = Profile::create(['name' => 'A', 'color' => '#0ea5e9', 'icon' => 'A',
            'treatment_start' => '2026-01-01', 'treatment_end' => '2026-12-31']);
        $b = Profile::create(['name' => 'B', 'color' => '#ef4444', 'icon' => 'B',
            'treatment_start' => '2026-01-01', 'treatment_end' => '2026-12-31']);
        app(ActiveProfile::class)->set($a->id);

        Livewire::test(ProfileSwitcher::class)->call('switchTo', $b->id);

        $this->assertSame($b->id, app(ActiveProfile::class)->id());
    }
}
