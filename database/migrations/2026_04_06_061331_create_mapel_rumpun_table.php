<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Create the pivot table
        Schema::create('mapel_rumpun', function (Blueprint $blueprint) {
            $blueprint->id();
            $blueprint->foreignId('mapel_id')->constrained()->cascadeOnDelete();
            $blueprint->foreignId('rumpun_id')->constrained()->cascadeOnDelete();
            $blueprint->timestamps();
        });

        // 2. Migrate existing data from mapels.rumpun_id to mapel_rumpun pivot table
        $mapelsWithRumpun = DB::table('mapels')->whereNotNull('rumpun_id')->get();
        foreach ($mapelsWithRumpun as $mapel) {
            DB::table('mapel_rumpun')->insert([
                'mapel_id' => $mapel->id,
                'rumpun_id' => $mapel->rumpun_id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // 3. Drop the old column from mapels table
        Schema::table('mapels', function (Blueprint $table) {
            $table->dropConstrainedForeignId('rumpun_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // 1. Re-add the rumpun_id column to mapels
        Schema::table('mapels', function (Blueprint $table) {
            $table->foreignId('rumpun_id')->nullable()->after('nama_mapel')->constrained('rumpuns')->nullOnDelete();
        });

        // 2. Restore the data from pivot table back to mapels (first rumpun found only)
        $pivotData = DB::table('mapel_rumpun')->orderBy('created_at')->get();
        foreach ($pivotData as $data) {
            DB::table('mapels')->where('id', $data->mapel_id)->update(['rumpun_id' => $data->rumpun_id]);
        }

        // 3. Drop the pivot table
        Schema::dropIfExists('mapel_rumpun');
    }
};
