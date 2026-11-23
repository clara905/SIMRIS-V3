<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('risiko_aset', function (Blueprint $table) {
            $table->id();

            $table->foreignId('risiko_id')
                ->constrained('risiko')
                ->restrictOnDelete();

            $table->foreignId('aset_idx')
                ->constrained(table: 'aset', column: 'idx')
                ->restrictOnDelete();

            $table->string('kode_aset_snapshot');
            $table->string('nama_aset_snapshot');
            $table->string('kategori_snapshot');
            $table->string('klasifikasi_snapshot');
            $table->string('unit_pemilik_snapshot');
            $table->string('lokasi_snapshot');
            $table->string('kondisi_snapshot');

            $table->dateTime('direkam_pada');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('risiko_aset');
    }
};
