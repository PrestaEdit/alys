<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('posology_history', function (Blueprint $table) {
            $table->decimal('dose_morning', 8, 2)->nullable()->after('dose');
            $table->decimal('dose_noon',    8, 2)->nullable()->after('dose_morning');
            $table->decimal('dose_evening', 8, 2)->nullable()->after('dose_noon');
        });
    }

    public function down(): void
    {
        Schema::table('posology_history', function (Blueprint $table) {
            $table->dropColumn(['dose_morning', 'dose_noon', 'dose_evening']);
        });
    }
};
