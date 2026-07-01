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
        // 1. Create rumpuns table
        Schema::create('rumpuns', function (Blueprint $table) {
            $table->id();
            $table->string('nama_rumpun')->unique();
            $table->timestamps();
        });

        // 2. Add rumpun_id to mapels
        Schema::table('mapels', function (Blueprint $table) {
            $table->foreignId('rumpun_id')->nullable()->after('nama_mapel')->constrained('rumpuns')->nullOnDelete();
        });

        // 3. Migrate data from mapels.rumpun (string) to rumpuns table
        $rumpuns = DB::table('mapels')->whereNotNull('rumpun')->distinct()->pluck('rumpun');
        foreach ($rumpuns as $name) {
            $id = DB::table('rumpuns')->insertGetId([
                'nama_rumpun' => $name,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            DB::table('mapels')->where('rumpun', $name)->update(['rumpun_id' => $id]);
        }

        // 4. Drop old rumpun column
        Schema::table('mapels', function (Blueprint $table) {
            $table->dropColumn('rumpun');
        });

        // 5. Add rumpun_ijazah_id to gurus
        Schema::table('gurus', function (Blueprint $table) {
            $table->foreignId('rumpun_ijazah_id')->nullable()->after('mapel_ijazah_id')->constrained('rumpuns')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('gurus', function (Blueprint $table) {
            $table->dropConstrainedForeignId('rumpun_ijazah_id');
        });

        Schema::table('mapels', function (Blueprint $table) {
            $table->string('rumpun')->nullable()->after('nama_mapel');
        });

        // Reverse data migration
        $mapels = DB::table('mapels')->get();
        foreach ($mapels as $mapel) {
            if ($mapel->rumpun_id) {
                $rumpunName = DB::table('rumpuns')->where('id', $mapel->rumpun_id)->value('nama_rumpun');
                DB::table('mapels')->where('id', $mapel->id)->update(['rumpun' => $rumpunName]);
            }
        }

        Schema::table('mapels', function (Blueprint $table) {
            $table->dropConstrainedForeignId('rumpun_id');
        });

        Schema::dropIfExists('rumpuns');
    }
};
