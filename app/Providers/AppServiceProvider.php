<?php

namespace App\Providers;

use App\Models\Setting;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    private const SEED_VERSION = 8;
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

            // Re-seed if first install or seed version is outdated
            $currentVersion = Schema::hasTable('settings')
                ? (int) Setting::get('seed_version', '0')
                : 0;

            if ($currentVersion < self::SEED_VERSION) {
                DB::table('calendar_events')->truncate();
                DB::table('posology_history')->truncate();
                DB::table('treatments')->truncate();
                DB::table('settings')->truncate();
                $this->app->make(DatabaseSeeder::class)->run();
                Setting::set('seed_version', (string) self::SEED_VERSION);
            }
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
