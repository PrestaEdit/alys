<?php

namespace Tests\Feature;

use App\Livewire\ProfileCreate;
use App\Livewire\Profiles;
use App\Models\Profile;
use App\Models\Setting;
use App\Services\ActiveProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ProfilesTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_persists_and_activates(): void
    {
        Livewire::test(ProfileCreate::class)
            ->set('name', 'Camille')
            ->set('color', '#10b981')
            ->set('treatmentStart', '2026-01-01')
            ->set('treatmentEnd', '2026-12-31')
            ->call('save')
            ->assertRedirect('/');

        $p = Profile::where('name', 'Camille')->first();
        $this->assertNotNull($p);
        $this->assertSame('C', $p->icon);
        $this->assertSame($p->id, app(ActiveProfile::class)->id());
    }

    public function test_create_rejects_invalid_color(): void
    {
        Livewire::test(ProfileCreate::class)
            ->set('name', 'Test')
            ->set('color', '#000000')
            ->set('treatmentStart', '2026-01-01')
            ->set('treatmentEnd', '2026-12-31')
            ->call('save')
            ->assertHasErrors(['color']);
    }

    public function test_create_rejects_duplicate_active_name(): void
    {
        Profile::create(['name' => 'Alys', 'color' => '#0ea5e9', 'icon' => 'A',
            'treatment_start' => '2026-01-01', 'treatment_end' => '2026-12-31']);

        Livewire::test(ProfileCreate::class)
            ->set('name', 'Alys')
            ->set('color', '#10b981')
            ->set('treatmentStart', '2026-01-01')
            ->set('treatmentEnd', '2026-12-31')
            ->call('save')
            ->assertHasErrors(['name']);
    }

    public function test_archive_active_profile_switches_to_next(): void
    {
        $a = Profile::create(['name' => 'A', 'color' => '#0ea5e9', 'icon' => 'A',
            'treatment_start' => '2026-01-01', 'treatment_end' => '2026-12-31']);
        $b = Profile::create(['name' => 'B', 'color' => '#ef4444', 'icon' => 'B',
            'treatment_start' => '2026-01-01', 'treatment_end' => '2026-12-31']);
        app(ActiveProfile::class)->set($a->id);

        Livewire::test(Profiles::class)->call('archive', $a->id);

        $this->assertSame($b->id, app(ActiveProfile::class)->id());
        $this->assertTrue($a->fresh()->isArchived());
    }

    public function test_archive_clears_active_when_no_other_profile(): void
    {
        $a = Profile::create(['name' => 'A', 'color' => '#0ea5e9', 'icon' => 'A',
            'treatment_start' => '2026-01-01', 'treatment_end' => '2026-12-31']);
        app(ActiveProfile::class)->set($a->id);

        Livewire::test(Profiles::class)->call('archive', $a->id);

        $this->assertNull(app(ActiveProfile::class)->id());
    }

    public function test_unarchive_restores_profile(): void
    {
        $a = Profile::create(['name' => 'A', 'color' => '#0ea5e9', 'icon' => 'A',
            'treatment_start' => '2026-01-01', 'treatment_end' => '2026-12-31']);
        $a->archive();

        Livewire::test(Profiles::class)->call('unarchive', $a->id);

        $this->assertFalse($a->fresh()->isArchived());
    }

    public function test_save_edit_updates_profile(): void
    {
        $p = Profile::create(['name' => 'Old', 'color' => '#0ea5e9', 'icon' => 'O',
            'treatment_start' => '2026-01-01', 'treatment_end' => '2026-12-31']);

        Livewire::test(Profiles::class)
            ->call('startEdit', $p->id)
            ->set('editName', 'New')
            ->set('editColor', '#10b981')
            ->set('editStart', '2026-02-01')
            ->set('editEnd', '2027-02-01')
            ->call('saveEdit');

        $fresh = $p->fresh();
        $this->assertSame('New', $fresh->name);
        $this->assertSame('N', $fresh->icon);
        $this->assertSame('#10b981', $fresh->color);
    }
}
