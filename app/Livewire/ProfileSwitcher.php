<?php

namespace App\Livewire;

use App\Models\Profile;
use App\Services\ActiveProfile;
use Livewire\Component;

class ProfileSwitcher extends Component
{
    public bool $open = false;

    public function toggle(): void
    {
        $this->open = ! $this->open;
    }

    public function switchTo(int $profileId)
    {
        $profile = Profile::active()->find($profileId);
        if ($profile) {
            app(ActiveProfile::class)->set($profile->id);
        }
        return redirect(request()->header('Referer', '/'));
    }

    public function render(): \Illuminate\View\View
    {
        $active = app(ActiveProfile::class)->get();
        $profiles = Profile::active()->orderBy('name')->get();

        return view('livewire.profile-switcher', [
            'active'   => $active,
            'profiles' => $profiles,
        ]);
    }
}
