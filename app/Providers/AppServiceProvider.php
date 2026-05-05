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
    }

    public function boot(): void
    {
        if ($this->app->environment('testing')) {
            return;
        }

        try {
            Artisan::call('migrate', ['--force' => true]);
            $this->bootstrapOnboardingFlag();
        } catch (\Throwable $e) {
            report($e);
        }
    }

    private function bootstrapOnboardingFlag(): void
    {
        if (! Schema::hasTable('settings')) {
            return;
        }

        $hasPatient = Setting::get('patient_name', '') !== '';
        $alreadyFlagged = Setting::get('onboarding_completed', '') !== '';

        if ($hasPatient && ! $alreadyFlagged) {
            Setting::set('onboarding_completed', '1');
        }
    }
}
