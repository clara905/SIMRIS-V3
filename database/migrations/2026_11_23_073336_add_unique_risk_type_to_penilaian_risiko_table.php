<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $hasDuplicateTypes = DB::table('penilaian_risiko')
            ->select('risiko_id', 'jenis_penilaian')
            ->groupBy('risiko_id', 'jenis_penilaian')
            ->havingRaw('COUNT(*) > 1')
            ->exists();

        if ($hasDuplicateTypes) {
            throw new RuntimeException(
                'Unique constraint ditolak karena terdapat lebih dari satu penilaian dengan jenis yang sama untuk risiko.'
            );
        }

        Schema::table('penilaian_risiko', function (Blueprint $table) {
            $table->unique(
                ['risiko_id', 'jenis_penilaian'],
                'uq_penilaian_risiko_risiko_jenis'
            );
        });

        Schema::table('penilaian_risiko', function (Blueprint $table) {
            $table->dropIndex('idx_penilaian_risiko_jenis');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasIndex('penilaian_risiko', 'idx_penilaian_risiko_jenis')) {
            Schema::table('penilaian_risiko', function (Blueprint $table) {
                $table->index(
                    ['risiko_id', 'jenis_penilaian'],
                    'idx_penilaian_risiko_jenis'
                );
            });
        }

        Schema::table('penilaian_risiko', function (Blueprint $table) {
            $table->dropUnique('uq_penilaian_risiko_risiko_jenis');
        });
    }
};
