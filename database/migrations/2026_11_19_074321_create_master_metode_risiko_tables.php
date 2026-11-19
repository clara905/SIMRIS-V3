
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('versi_metode_risiko', function (Blueprint $table) {
            $table->id();
            $table->string('kode_versi', 50)->unique();
            $table->string('nama', 255);
            $table->text('deskripsi')->nullable();
            $table->boolean('dibekukan')->default(false);
            $table->dateTime('dibekukan_pada')->nullable();
        });

        Schema::create('skala_kemungkinan', function (Blueprint $table) {
            $table->id();

            $table->foreignId('versi_metode_id')
                ->constrained('versi_metode_risiko')
                ->restrictOnDelete();

            $table->unsignedTinyInteger('nilai');
            $table->string('nama', 100);
            $table->text('deskripsi')->nullable();

            $table->unique(
                ['versi_metode_id', 'nilai'],
                'uq_skala_kemungkinan_versi_nilai'
            );
        });

        Schema::create('skala_dampak', function (Blueprint $table) {
            $table->id();

            $table->foreignId('versi_metode_id')
                ->constrained('versi_metode_risiko')
                ->restrictOnDelete();

            $table->unsignedTinyInteger('nilai');
            $table->string('nama', 100);
            $table->text('deskripsi')->nullable();

            $table->unique(
                ['versi_metode_id', 'nilai'],
                'uq_skala_dampak_versi_nilai'
            );
        });

        Schema::create('tingkat_risiko', function (Blueprint $table) {
            $table->id();

            $table->foreignId('versi_metode_id')
                ->constrained('versi_metode_risiko')
                ->restrictOnDelete();

            $table->string('kode', 50);
            $table->string('nama', 100);
            $table->unsignedTinyInteger('urutan');

            $table->unique(
                ['versi_metode_id', 'kode'],
                'uq_tingkat_risiko_versi_kode'
            );

            $table->unique(
                ['versi_metode_id', 'urutan'],
                'uq_tingkat_risiko_versi_urutan'
            );
        });

        Schema::create('matriks_risiko', function (Blueprint $table) {
            $table->id();

            $table->foreignId('versi_metode_id')
                ->constrained('versi_metode_risiko')
                ->restrictOnDelete();

            $table->foreignId('kemungkinan_id')
                ->constrained('skala_kemungkinan')
                ->restrictOnDelete();

            $table->foreignId('dampak_id')
                ->constrained('skala_dampak')
                ->restrictOnDelete();

            $table->foreignId('tingkat_risiko_id')
                ->constrained('tingkat_risiko')
                ->restrictOnDelete();

            $table->unsignedInteger('skor');
            $table->string('rekomendasi_keputusan', 255)->nullable();

            $table->unique(
                ['versi_metode_id', 'kemungkinan_id', 'dampak_id'],
                'uq_matriks_versi_kemungkinan_dampak'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('matriks_risiko');
        Schema::dropIfExists('tingkat_risiko');
        Schema::dropIfExists('skala_dampak');
        Schema::dropIfExists('skala_kemungkinan');
        Schema::dropIfExists('versi_metode_risiko');
    }
};
