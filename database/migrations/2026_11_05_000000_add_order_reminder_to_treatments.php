<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('treatments', function (Blueprint $table) {
            $table->boolean('order_reminder_enabled')->default(false)->after('notification_time_evening');
            $table->unsignedSmallInteger('order_reminder_days_before')->default(3)->after('order_reminder_enabled');
            $table->string('order_reminder_time', 5)->nullable()->after('order_reminder_days_before');
        });
    }

    public function down(): void
    {
        Schema::table('treatments', function (Blueprint $table) {
            $table->dropColumn(['order_reminder_enabled', 'order_reminder_days_before', 'order_reminder_time']);
        });
    }
};
