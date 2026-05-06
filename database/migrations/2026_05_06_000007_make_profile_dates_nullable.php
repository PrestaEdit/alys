<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('profiles', function (Blueprint $table) {
            $table->date('treatment_start')->nullable()->change();
            $table->date('treatment_end')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('profiles', function (Blueprint $table) {
            $table->date('treatment_start')->nullable(false)->change();
            $table->date('treatment_end')->nullable(false)->change();
        });
    }
};
