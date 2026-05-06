<?php

namespace Tests\Feature;

use App\Models\Profile;
use App\Models\Setting;
use App\Services\ActiveProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ActiveProfileServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_id_reads_from_settings(): void
    {
        Setting::set('active_profile_id', '42');
        $service = new ActiveProfile();
        $this->assertSame(42, $service->id());
    }

    public function test_set_persists_and_returns(): void
    {
        $profile = Profile::create(['name' => 'Test', 'treatment_start' => '2026-01-01', 'treatment_end' => '2026-12-31']);
        $service = new ActiveProfile();
        $service->set($profile->id);

        $this->assertSame($profile->id, $service->id());
        $this->assertSame($profile->id, $service->get()->id);
        $this->assertSame((string) $profile->id, Setting::get('active_profile_id'));
    }

    public function test_get_returns_null_when_no_profile(): void
    {
        $service = new ActiveProfile();
        $this->assertNull($service->get());
    }
}
