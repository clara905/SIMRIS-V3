<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dokumen', function (Blueprint $table) {
            $table->id();

            $table->foreignId('unit_pemilik_id')
                ->constrained('unit_organisasi')
                ->restrictOnDelete();

            $table->foreignId('dibuat_oleh')
                ->constrained('users')
                ->restrictOnDelete();

            $table->string('kode', 100)->unique();
            $table->string('judul', 255);
            $table->string('jenis', 100);
            $table->string('nama_file_asli', 255);
            $table->string('storage_key')->unique();
            $table->string('ekstensi', 20);
            $table->string('mime_type', 150);
            $table->unsignedBigInteger('ukuran_byte');
            $table->string('checksum_sha256', 64);
            $table->string('status_pemeriksaan', 50);
            $table->dateTime('dibuat_pada');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dokumen');
    }
};
