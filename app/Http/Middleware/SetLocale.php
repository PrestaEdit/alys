<?php

namespace App\Http\Middleware;

use App\Models\Setting;
use Carbon\Carbon;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    /** Langues supportées par l'application (la 1re est le fallback). */
    private const SUPPORTED = ['fr', 'en'];

    public function handle(Request $request, Closure $next): Response
    {
        $locale = $this->resolve($request);

        app()->setLocale($locale);
        Carbon::setLocale($locale);

        return $next($request);
    }

    private function resolve(Request $request): string
    {
        // 1. Choix manuel persisté
        $stored = Setting::get('locale', '');
        if (in_array($stored, self::SUPPORTED, true)) {
            return $stored;
        }

        // 2. Auto-détection depuis l'Accept-Language de la WebView.
        //    getPreferredLanguage() retombe sur la 1re entrée ('fr') quand
        //    aucune langue supportée n'est demandée → fallback français.
        return $request->getPreferredLanguage(self::SUPPORTED);
    }
}
