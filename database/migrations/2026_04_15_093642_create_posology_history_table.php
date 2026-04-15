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
        Schema::create('posology_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('treatment_id')->constrained()->cascadeOnDelete();
            $table->decimal('dose', 8, 2);
            $table->text('note')->nullable();
            $table->date('started_at');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('posology_history');
    }
};
