<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bukti_kondisi_aset', function (Blueprint $table) {
            $table->id();

            $table->foreignId('risiko_id')
                ->constrained('risiko')
                ->restrictOnDelete();

            $table->foreignId('dokumen_id')
                ->constrained('dokumen')
                ->restrictOnDelete();

            $table->text('keterangan');
            $table->dateTime('dibuat_pada');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bukti_kondisi_aset');
    }
};
