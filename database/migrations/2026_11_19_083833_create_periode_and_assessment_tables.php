
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('periode_penilaian', function (Blueprint $table) {
            $table->id();

            $table->foreignId('unit_id')
                ->constrained('unit_organisasi')
                ->restrictOnDelete();

            $table->foreignId('dibuat_oleh')
                ->constrained('users')
                ->restrictOnDelete();

            $table->string('nama_periode', 255);
            $table->date('tanggal_mulai');
            $table->date('tanggal_selesai');
            $table->string('status', 20)->default('DRAFT');

            $table->dateTime('dibuat_pada');
            $table->dateTime('diperbarui_pada');

            $table->index(['unit_id', 'status']);
            $table->index(['tanggal_mulai', 'tanggal_selesai']);
        });

        Schema::create('seri_asesmen', function (Blueprint $table) {
            $table->id();

            $table->string('kode', 100)->unique();
            $table->string('nama', 255);
        });

        Schema::create('asesmen_risiko', function (Blueprint $table) {
            $table->id();

            $table->foreignId('seri_asesmen_id')
                ->constrained('seri_asesmen')
                ->restrictOnDelete();

            $table->foreignId('versi_sebelumnya_id')
                ->nullable()
                ->constrained('asesmen_risiko')
                ->restrictOnDelete();

            $table->foreignId('periode_id')
                ->constrained('periode_penilaian')
                ->restrictOnDelete();

            $table->foreignId('unit_id')
                ->constrained('unit_organisasi')
                ->restrictOnDelete();

            $table->foreignId('dibuat_oleh')
                ->constrained('users')
                ->restrictOnDelete();

            $table->text('tujuan');
            $table->text('ruang_lingkup');

            $table->string('status', 20)->default('DRAFT');
            $table->unsignedSmallInteger('nomor_versi');

            $table->dateTime('dibuat_pada');
            $table->dateTime('dibekukan_pada')->nullable();

            $table->unique(
                ['seri_asesmen_id', 'nomor_versi'],
                'uq_asesmen_seri_nomor_versi'
            );

            $table->index(['unit_id', 'status']);
            $table->index('periode_id');
            $table->index('versi_sebelumnya_id');
        });

        if (in_array(DB::getDriverName(), ['mysql', 'mariadb', 'pgsql'], true)) {
            DB::statement("
                ALTER TABLE periode_penilaian
                ADD CONSTRAINT chk_periode_penilaian_status
                CHECK (status IN (
                    'DRAFT',
                    'AKTIF',
                    'SELESAI',
                    'DITUTUP'
                ))
            ");

            DB::statement('
                ALTER TABLE periode_penilaian
                ADD CONSTRAINT chk_periode_penilaian_tanggal
                CHECK (tanggal_selesai >= tanggal_mulai)
            ');

            DB::statement("
                ALTER TABLE asesmen_risiko
                ADD CONSTRAINT chk_asesmen_risiko_status
                CHECK (status IN (
                    'DRAFT',
                    'DIAJUKAN',
                    'DIREVIU',
                    'DIKEMBALIKAN',
                    'DISAHKAN'
                ))
            ");

            DB::statement('
                ALTER TABLE asesmen_risiko
                ADD CONSTRAINT chk_asesmen_risiko_nomor_versi
                CHECK (nomor_versi >= 1)
            ');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('asesmen_risiko');
        Schema::dropIfExists('seri_asesmen');
        Schema::dropIfExists('periode_penilaian');
    }
};
