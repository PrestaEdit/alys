<?php

namespace Tests\Feature;

use App\Models\Profile;
use App\Models\Treatment;
use App\Services\ActiveProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BelongsToActiveProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_filters_by_active_profile(): void
    {
        $a = Profile::create(['name' => 'A', 'treatment_start' => '2026-01-01', 'treatment_end' => '2026-12-31']);
        $b = Profile::create(['name' => 'B', 'treatment_start' => '2026-01-01', 'treatment_end' => '2026-12-31']);

        Treatment::withoutGlobalScopes()->create(['name' => 'TA', 'type' => 'daily', 'profile_id' => $a->id]);
        Treatment::withoutGlobalScopes()->create(['name' => 'TB', 'type' => 'daily', 'profile_id' => $b->id]);

        $service = $this->app->make(ActiveProfile::class);

        $service->set($a->id);
        $this->assertSame(['TA'], Treatment::pluck('name')->all());

        $service->set($b->id);
        $this->assertSame(['TB'], Treatment::pluck('name')->all());
    }

    public function test_auto_assigns_profile_id_on_create(): void
    {
        $p = Profile::create(['name' => 'P', 'treatment_start' => '2026-01-01', 'treatment_end' => '2026-12-31']);
        $this->app->make(ActiveProfile::class)->set($p->id);

        $t = Treatment::create(['name' => 'X', 'type' => 'daily']);

        $this->assertSame($p->id, $t->profile_id);
    }

    public function test_throws_when_creating_without_active_profile(): void
    {
        $this->expectException(\RuntimeException::class);
        Treatment::create(['name' => 'X', 'type' => 'daily']);
    }
}
