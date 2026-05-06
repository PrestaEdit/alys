<?php

namespace App\Services;

use App\Models\Profile;
use App\Models\Setting;

class ActiveProfile
{
    private ?int $cachedId = null;
    private bool $cacheLoaded = false;
    private ?Profile $cachedProfile = null;

    public function id(): ?int
    {
        if (! $this->cacheLoaded) {
            $value = Setting::get('active_profile_id', '');
            $this->cachedId = $value !== '' ? (int) $value : null;
            $this->cacheLoaded = true;
        }
        return $this->cachedId;
    }

    public function get(): ?Profile
    {
        if ($this->cachedProfile !== null) {
            return $this->cachedProfile;
        }
        $id = $this->id();
        if ($id === null) {
            return null;
        }
        return $this->cachedProfile = Profile::find($id);
    }

    public function set(int $id): void
    {
        Setting::set('active_profile_id', (string) $id);
        $this->cachedId = $id;
        $this->cacheLoaded = true;
        $this->cachedProfile = null;
    }

    public function clear(): void
    {
        Setting::set('active_profile_id', '');
        $this->cachedId = null;
        $this->cacheLoaded = true;
        $this->cachedProfile = null;
    }
}
