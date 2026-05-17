<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('treatments', function (Blueprint $table) {
            $table->integer('sort_order')->nullable()->after('archived_at');
        });

        $ids = DB::table('treatments')
            ->whereNull('archived_at')
            ->orderByRaw("CASE WHEN name = 'Hôpital' THEN 0 ELSE 1 END")
            ->orderBy('id')
            ->pluck('id');

        foreach ($ids as $index => $id) {
            DB::table('treatments')->where('id', $id)->update(['sort_order' => $index]);
        }
    }

    public function down(): void
    {
        Schema::table('treatments', function (Blueprint $table) {
            $table->dropColumn('sort_order');
        });
    }
};
