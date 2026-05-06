<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        foreach (['treatments', 'calendar_events', 'posology_history'] as $table) {
            Schema::table($table, function (Blueprint $t) {
                $t->foreignId('profile_id')->nullable(false)->change();
            });
        }
    }

    public function down(): void
    {
        foreach (['treatments', 'calendar_events', 'posology_history'] as $table) {
            Schema::table($table, function (Blueprint $t) {
                $t->foreignId('profile_id')->nullable()->change();
            });
        }
    }
};
