<?php

namespace App\Providers;

use Database\Seeders\DatabaseSeeder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(\App\Services\CalendarService::class);
        $this->app->singleton(\App\Services\EventMoveService::class);
        $this->app->singleton(\App\Services\ExportService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->bootDatabase();
    }

    private function bootDatabase(): void
    {
        if ($this->app->environment('testing')) {
            return;
        }

        try {
            // Run migrations if needed (idempotent)
            Artisan::call('migrate', ['--force' => true]);

            // Seed only if the treatments table is empty (first install)
            if (Schema::hasTable('treatments') && DB::table('treatments')->count() === 0) {
                $this->app->make(DatabaseSeeder::class)->run();
            }
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
