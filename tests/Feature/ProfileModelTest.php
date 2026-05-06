<?php

namespace Tests\Feature;

use App\Models\Profile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_active_scope_excludes_archived(): void
    {
        $a = Profile::create(['name' => 'A', 'treatment_start' => '2026-01-01', 'treatment_end' => '2026-12-31']);
        $b = Profile::create(['name' => 'B', 'treatment_start' => '2026-01-01', 'treatment_end' => '2026-12-31']);
        $b->archive();

        $active = Profile::active()->pluck('id')->all();

        $this->assertSame([$a->id], $active);
    }

    public function test_archive_and_unarchive(): void
    {
        $p = Profile::create(['name' => 'X', 'treatment_start' => '2026-01-01', 'treatment_end' => '2026-12-31']);
        $this->assertFalse($p->isArchived());
        $p->archive();
        $this->assertTrue($p->fresh()->isArchived());
        $p->unarchive();
        $this->assertFalse($p->fresh()->isArchived());
    }
}
