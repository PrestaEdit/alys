<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('treatments', function (Blueprint $table) {
            $table->foreignId('parent_treatment_id')->nullable()->constrained('treatments')->nullOnDelete()->after('linked_days');
            $table->unsignedTinyInteger('linked_days')->nullable()->after('recurrence_start');
        });
    }

    public function down(): void
    {
        Schema::table('treatments', function (Blueprint $table) {
            $table->dropForeign(['parent_treatment_id']);
            $table->dropColumn(['parent_treatment_id', 'linked_days']);
        });
    }
};
