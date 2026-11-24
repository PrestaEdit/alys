<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('treatments', function (Blueprint $table) {
            // Pause temporaire : le traitement est suspendu du paused_from au
            // paused_until inclus. paused_until vide = pause sans date de reprise.
            $table->date('paused_from')->nullable()->after('archived_at');
            $table->date('paused_until')->nullable()->after('paused_from');
        });
    }

    public function down(): void
    {
        Schema::table('treatments', function (Blueprint $table) {
            $table->dropColumn(['paused_from', 'paused_until']);
        });
    }
};
