<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bukti_penilaian', function (Blueprint $table) {
            $table->foreignId('penilaian_id')->constrained('penilaian_risiko')->restrictOnDelete();
            $table->foreignId('dokumen_id')->constrained('dokumen')->restrictOnDelete();
            $table->text('keterangan');
            $table->primary(['penilaian_id', 'dokumen_id']);
        });

        Schema::create('bukti_penanganan', function (Blueprint $table) {
            $table->foreignId('progres_penanganan_id')->constrained('progres_penanganan')->restrictOnDelete();
            $table->foreignId('dokumen_id')->constrained('dokumen')->restrictOnDelete();
            $table->text('keterangan');
            $table->primary(['progres_penanganan_id', 'dokumen_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bukti_penanganan');
        Schema::dropIfExists('bukti_penilaian');
    }
};
