<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('treatments', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('commercial_name')->nullable();
            $table->enum('type', ['daily', 'weekly', 'cyclic', 'medical_act']);
            $table->string('unit')->nullable();
            $table->decimal('current_dose', 8, 2)->nullable();
            $table->string('color', 7)->default('#6b7280');
            $table->unsignedTinyInteger('frequency_weeks')->nullable();
            $table->unsignedTinyInteger('day_of_week')->nullable();
            $table->date('recurrence_start')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('treatments');
    }
};
