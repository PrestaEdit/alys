<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->validateCsrfTokens(except: [
            'livewire/*',
            '_native/*',
        ]);
        $middleware->web(prepend: [
            \App\Http\Middleware\SetLocale::class,
        ]);
        $middleware->web(append: [
            \App\Http\Middleware\EnsureOnboardingCompleted::class,
            \App\Http\Middleware\PreventBfcache::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        \Sentry\Laravel\Integration::handles($exceptions);

        // TEMPORAIRE (builds internes uniquement) : affiche l'exception réelle à
        // l'écran au lieu du « Erreur 500 » générique de NativePHP. Renvoyée en
        // HTTP 200 pour que la webview l'affiche comme une page normale.
        // Gaté par ALYS_DEBUG_ERRORS — NE JAMAIS activer en prod publique.
        $exceptions->render(function (\Throwable $e, $request) {
            if (! env('ALYS_DEBUG_ERRORS', false)) {
                return null;
            }

            $html = '<div style="font-family:monospace;padding:16px;white-space:pre-wrap;word-break:break-word">'
                .'<h2>'.e(get_class($e)).'</h2>'
                .'<p><b>'.e($e->getMessage()).'</b></p>'
                .'<p>'.e($e->getFile()).':'.$e->getLine().'</p>'
                .'<hr><pre>'.e($e->getTraceAsString()).'</pre>'
                .'</div>';

            return response($html, 200);
        });
    })->create();
