<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('penilaian_risiko', function (Blueprint $table) {
            $table->id();

            $table->foreignId('risiko_id')
                ->constrained('risiko')
                ->restrictOnDelete();

            $table->foreignId('matriks_id')
                ->constrained('matriks_risiko')
                ->restrictOnDelete();

            $table->foreignId('dinilai_oleh')
                ->constrained('users')
                ->restrictOnDelete();

            $table->string('jenis_penilaian', 20);
            $table->text('alasan_kemungkinan');
            $table->text('alasan_dampak');
            $table->text('kesimpulan');
            $table->dateTime('dinilai_pada');

            $table->index(
                ['risiko_id', 'jenis_penilaian'],
                'idx_penilaian_risiko_jenis'
            );
        });

        if (in_array(DB::getDriverName(), ['mysql', 'mariadb', 'pgsql'], true)) {
            DB::statement("
                ALTER TABLE penilaian_risiko
                ADD CONSTRAINT chk_penilaian_risiko_jenis
                CHECK (jenis_penilaian IN ('INHEREN', 'RESIDUAL'))
            ");
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('penilaian_risiko');
    }
};
