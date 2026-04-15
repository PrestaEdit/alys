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
        Schema::create('calendar_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('treatment_id')->constrained()->cascadeOnDelete();
            $table->date('scheduled_date');
            $table->date('original_date')->nullable();
            $table->boolean('is_cancelled')->default(false);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['scheduled_date', 'is_cancelled']);
            $table->index('treatment_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('calendar_events');
    }
};
