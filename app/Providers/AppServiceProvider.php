<?php

namespace App\Providers;

use App\Models\Setting;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(\App\Services\CalendarService::class);
        $this->app->singleton(\App\Services\EventMoveService::class);
        $this->app->singleton(\App\Services\ExportService::class);
        $this->app->singleton(\App\Services\ActiveProfile::class);
        $this->app->singleton(\App\Services\CryptoService::class);
        $this->app->singleton(\App\Services\ImportService::class);
        $this->app->singleton(\App\Services\NotificationScheduler::class);
    }

    public function boot(): void
    {
        if ($this->app->environment('testing')) {
            return;
        }

        try {
            Artisan::call('migrate', ['--force' => true]);
            $this->bootstrapOnboardingFlag();
            $this->bootstrapDeviceKeys();
            $this->requestNotificationPermission();
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('[boot] ' . $e->getMessage());
        }
    }

    private function bootstrapOnboardingFlag(): void
    {
        if (! Schema::hasTable('settings') || ! Schema::hasTable('profiles')) {
            return;
        }

        $hasProfile = \App\Models\Profile::query()->exists();
        $alreadyFlagged = Setting::get('onboarding_completed', '') !== '';

        if ($hasProfile && ! $alreadyFlagged) {
            Setting::set('onboarding_completed', '1');
        }
    }

    private function requestNotificationPermission(): void
    {
        try {
            \Ikromjon\LocalNotifications\Facades\LocalNotifications::requestPermission();
        } catch (\Throwable) {
            // Non bloquant — l'utilisateur peut refuser
        }
    }

    private function bootstrapDeviceKeys(): void
    {
        if (\Native\Mobile\Facades\SecureStorage::get('device_key') !== null) {
            return;
        }

        $key = $this->app->make(\App\Services\CryptoService::class)->generateKey();
        \Native\Mobile\Facades\SecureStorage::set('device_key', $key);
    }
}
