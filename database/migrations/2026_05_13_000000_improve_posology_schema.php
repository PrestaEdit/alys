<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Make dose nullable so daypart records don't require a global dose
        Schema::table('posology_history', function (Blueprint $table) {
            $table->decimal('dose', 8, 2)->nullable()->change();
            $table->unsignedTinyInteger('times_per_day')->nullable()->after('dose_evening');
        });

        Schema::table('treatments', function (Blueprint $table) {
            $table->unsignedTinyInteger('times_per_day')->nullable()->after('dose_evening');
        });
    }

    public function down(): void
    {
        Schema::table('posology_history', function (Blueprint $table) {
            $table->decimal('dose', 8, 2)->nullable(false)->change();
            $table->dropColumn('times_per_day');
        });

        Schema::table('treatments', function (Blueprint $table) {
            $table->dropColumn('times_per_day');
        });
    }
};
