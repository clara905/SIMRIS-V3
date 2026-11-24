<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $hasDuplicateAssessmentCodes = DB::table('risiko')
            ->select('asesmen_id', 'kode')
            ->groupBy('asesmen_id', 'kode')
            ->havingRaw('COUNT(*) > 1')
            ->exists();

        if ($hasDuplicateAssessmentCodes) {
            throw new RuntimeException(
                'Migration dibatalkan: ditemukan kode risiko duplikat dalam asesmen yang sama.'
            );
        }

        Schema::table('risiko', function (Blueprint $table) {
            $table->dropUnique('risiko_kode_unique');
            $table->unique(
                ['asesmen_id', 'kode'],
                'uq_risiko_asesmen_kode'
            );
        });
    }

    public function down(): void
    {
        $hasDuplicateRiskCodes = DB::table('risiko')
            ->select('kode')
            ->groupBy('kode')
            ->havingRaw('COUNT(*) > 1')
            ->exists();

        if ($hasDuplicateRiskCodes) {
            throw new RuntimeException(
                'Rollback dibatalkan: kode risiko yang sama sudah digunakan pada asesmen berbeda.'
            );
        }

        if (! Schema::hasIndex('risiko', 'risiko_asesmen_id_foreign')) {
            Schema::table('risiko', function (Blueprint $table) {
                $table->index(
                    'asesmen_id',
                    'risiko_asesmen_id_foreign'
                );
            });
        }

        Schema::table('risiko', function (Blueprint $table) {
            $table->dropUnique('uq_risiko_asesmen_kode');
            $table->unique('kode', 'risiko_kode_unique');
        });
    }
};
