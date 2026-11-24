<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sop', function (Blueprint $table) {
            $table->id();
            $table->foreignId('unit_pemilik_id')->constrained('unit_organisasi')->restrictOnDelete();
            $table->string('kode', 100);
            $table->string('judul', 255);
            $table->text('deskripsi');
            $table->boolean('aktif')->default(true);
            $table->unique(['unit_pemilik_id', 'kode'], 'uq_sop_unit_kode');
        });

        Schema::create('sop_versi', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sop_id')->constrained('sop')->restrictOnDelete();
            $table->foreignId('dokumen_id')->constrained('dokumen')->restrictOnDelete();
            $table->foreignId('dibuat_oleh')->constrained('users')->restrictOnDelete();
            $table->string('nomor_versi', 100);
            $table->date('tanggal_berlaku');
            $table->string('status', 50);
            $table->dateTime('dibuat_pada');
            $table->unique(['sop_id', 'nomor_versi'], 'uq_sop_versi_sop_nomor');
        });

        Schema::create('sop_review', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sop_versi_id')->constrained('sop_versi')->restrictOnDelete();
            $table->foreignId('reviewer_id')->constrained('users')->restrictOnDelete();
            $table->string('tahap', 50);
            $table->string('keputusan', 100);
            $table->text('catatan');
            $table->dateTime('waktu');
        });

        Schema::create('sop_kontrol', function (Blueprint $table) {
            $table->foreignId('sop_versi_id')->constrained('sop_versi')->restrictOnDelete();
            $table->foreignId('kontrol_id')->constrained('kontrol_keamanan')->restrictOnDelete();
            $table->primary(['sop_versi_id', 'kontrol_id']);
        });

        Schema::create('risiko_sop', function (Blueprint $table) {
            $table->foreignId('risiko_id')->constrained('risiko')->restrictOnDelete();
            $table->foreignId('sop_versi_id')->constrained('sop_versi')->restrictOnDelete();
            $table->string('kode_sop_snapshot', 100);
            $table->string('judul_sop_snapshot', 255);
            $table->string('nomor_versi_snapshot', 100);
            $table->text('uraian_penerapan');
            $table->primary(['risiko_id', 'sop_versi_id']);
        });

        Schema::create('penanganan_sop', function (Blueprint $table) {
            $table->foreignId('rencana_penanganan_id')->constrained('rencana_penanganan')->restrictOnDelete();
            $table->foreignId('sop_versi_id')->constrained('sop_versi')->restrictOnDelete();
            $table->primary(['rencana_penanganan_id', 'sop_versi_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('penanganan_sop');
        Schema::dropIfExists('risiko_sop');
        Schema::dropIfExists('sop_kontrol');
        Schema::dropIfExists('sop_review');
        Schema::dropIfExists('sop_versi');
        Schema::dropIfExists('sop');
    }
};
