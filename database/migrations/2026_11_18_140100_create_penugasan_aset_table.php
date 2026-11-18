<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('penugasan_aset', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('aset_idx');

            $table->foreign('aset_idx')
                ->references('idx')
                ->on('aset')
                ->restrictOnDelete();

            $table->foreignId('pegawai_id')
                ->constrained('pegawai')
                ->restrictOnDelete();

            $table->string('jenis_tanggung_jawab', 100);
            $table->date('mulai');
            $table->date('selesai')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('penugasan_aset');
    }
};
