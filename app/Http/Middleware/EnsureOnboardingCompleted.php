<?php

namespace App\Http\Middleware;

use App\Models\Setting;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureOnboardingCompleted
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->is('onboarding') || $request->is('onboarding/*') || $request->is('livewire/*')) {
            return $next($request);
        }

        if (Setting::get('onboarding_completed', '') !== '1') {
            return redirect('/onboarding');
        }

        return $next($request);
    }
}
