<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('height_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('profile_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('height_cm');
            $table->date('measured_on');
            $table->timestamps();

            // Une mesure par jour et par profil, comme pour le poids.
            $table->unique(['profile_id', 'measured_on']);
        });

        // La taille déjà saisie devient le premier point de l'historique.
        $now = now();
        DB::table('profiles')->whereNotNull('height_cm')->orderBy('id')->get()
            ->each(fn ($p) => DB::table('height_entries')->insert([
                'profile_id'  => $p->id,
                'height_cm'   => $p->height_cm,
                'measured_on' => substr((string) ($p->updated_at ?? $now), 0, 10),
                'created_at'  => $now,
                'updated_at'  => $now,
            ]));
    }

    public function down(): void
    {
        Schema::dropIfExists('height_entries');
    }
};
