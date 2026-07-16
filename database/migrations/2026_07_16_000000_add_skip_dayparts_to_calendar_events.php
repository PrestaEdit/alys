<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('calendar_events', function (Blueprint $table) {
            $table->boolean('skip_morning')->default(false);
            $table->boolean('skip_noon')->default(false);
            $table->boolean('skip_evening')->default(false);
        });
    }

    public function down(): void
    {
        Schema::table('calendar_events', function (Blueprint $table) {
            $table->dropColumn(['skip_morning', 'skip_noon', 'skip_evening']);
        });
    }
};
