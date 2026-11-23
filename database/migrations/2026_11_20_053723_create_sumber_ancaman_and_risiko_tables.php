<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sumber_ancaman', function (Blueprint $table) {
            $table->id();
            $table->string('kode', 10)->unique();
            $table->string('nama', 100);
        });

        Schema::create('risiko', function (Blueprint $table) {
            $table->id();

            $table->foreignId('asesmen_id')
                ->constrained('asesmen_risiko')
                ->restrictOnDelete();

            $table->foreignId('ancaman_id')
                ->constrained('ancaman')
                ->restrictOnDelete();

            $table->foreignId('sumber_ancaman_id')
                ->constrained('sumber_ancaman')
                ->restrictOnDelete();

            $table->foreignId('kerentanan_id')
                ->constrained('kerentanan')
                ->restrictOnDelete();

            $table->foreignId('pemilik_risiko_id')
                ->constrained('pegawai')
                ->restrictOnDelete();

            $table->foreignId('dibuat_oleh')
                ->constrained('users')
                ->restrictOnDelete();

            $table->string('kode')->unique();
            $table->text('skenario');
            $table->text('uraian_kerentanan');
            $table->text('uraian_dampak');
            $table->boolean('arah_dampak');
            $table->dateTime('dibuat_pada');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('risiko');
        Schema::dropIfExists('sumber_ancaman');
    }
};
