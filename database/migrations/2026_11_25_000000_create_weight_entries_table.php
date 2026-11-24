<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('weight_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('profile_id')->constrained()->cascadeOnDelete();
            $table->decimal('weight_kg', 5, 1);
            $table->date('measured_on');
            $table->timestamps();

            // Une pesée par jour et par profil : une nouvelle saisie le même
            // jour corrige la précédente au lieu de s'empiler.
            $table->unique(['profile_id', 'measured_on']);
        });

        // Le poids déjà saisi devient le premier point de l'historique, daté
        // de la dernière modification du profil.
        $now = now();
        DB::table('profiles')->whereNotNull('weight_kg')->orderBy('id')->get()
            ->each(fn ($p) => DB::table('weight_entries')->insert([
                'profile_id'  => $p->id,
                'weight_kg'   => $p->weight_kg,
                'measured_on' => substr((string) ($p->updated_at ?? $now), 0, 10),
                'created_at'  => $now,
                'updated_at'  => $now,
            ]));
    }

    public function down(): void
    {
        Schema::dropIfExists('weight_entries');
    }
};
