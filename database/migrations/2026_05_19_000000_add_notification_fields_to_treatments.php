<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('treatments', function (Blueprint $table) {
            $table->boolean('notification_enabled')->default(false)->after('sort_order');
            $table->string('notification_time_morning', 5)->nullable()->after('notification_enabled');
            $table->string('notification_time_noon',    5)->nullable()->after('notification_time_morning');
            $table->string('notification_time_evening', 5)->nullable()->after('notification_time_noon');
        });
    }

    public function down(): void
    {
        Schema::table('treatments', function (Blueprint $table) {
            $table->dropColumn([
                'notification_enabled',
                'notification_time_morning',
                'notification_time_noon',
                'notification_time_evening',
            ]);
        });
    }
};
