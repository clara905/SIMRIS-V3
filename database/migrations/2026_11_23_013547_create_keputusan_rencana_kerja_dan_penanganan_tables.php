<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('usulan_keputusan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('risiko_id')->constrained('risiko')->restrictOnDelete();
            $table->foreignId('diusulkan_oleh')->constrained('users')->restrictOnDelete();
            $table->string('keputusan', 100);
            $table->text('alasan');
            $table->dateTime('diusulkan_pada');
        });

        Schema::create('keputusan_risiko', function (Blueprint $table) {
            $table->id();
            $table->foreignId('risiko_id')->unique()->constrained('risiko')->restrictOnDelete();
            $table->foreignId('diputuskan_oleh')->constrained('users')->restrictOnDelete();
            $table->string('keputusan', 100);
            $table->text('alasan');
            $table->dateTime('diputuskan_pada');
        });

        Schema::create('review_asesmen', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asesmen_id')->constrained('asesmen_risiko')->restrictOnDelete();
            $table->foreignId('reviewer_id')->constrained('users')->restrictOnDelete();
            $table->string('tahap', 50);
            $table->string('keputusan', 100);
            $table->text('catatan');
            $table->dateTime('waktu');
        });

        Schema::create('rencana_kerja', function (Blueprint $table) {
            $table->id();
            $table->foreignId('satker_id')->constrained('unit_organisasi')->restrictOnDelete();
            $table->string('kode', 100);
            $table->string('nama', 255);
            $table->text('deskripsi');
            $table->boolean('aktif')->default(true);
            $table->foreignId('dibuat_oleh')->constrained('users')->restrictOnDelete();
            $table->dateTime('dibuat_pada');
            $table->dateTime('diperbarui_pada');
            $table->unique(['satker_id', 'kode'], 'uq_rencana_kerja_satker_kode');
        });

        Schema::create('risiko_rencana_kerja', function (Blueprint $table) {
            $table->foreignId('risiko_id')->constrained('risiko')->restrictOnDelete();
            $table->foreignId('rencana_kerja_id')->constrained('rencana_kerja')->restrictOnDelete();
            $table->string('kode_snapshot', 100);
            $table->string('nama_snapshot', 255);
            $table->text('deskripsi_snapshot');
            $table->primary(['risiko_id', 'rencana_kerja_id']);
        });

        Schema::create('rencana_penanganan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asesmen_id')->constrained('asesmen_risiko')->restrictOnDelete();
            $table->foreignId('pic_id')->constrained('pegawai')->restrictOnDelete();
            $table->string('kode', 100);
            $table->text('uraian');
            $table->date('target_selesai');
            $table->string('status', 50);
            $table->dateTime('dibuat_pada');
            $table->unique(['asesmen_id', 'kode'], 'uq_rencana_penanganan_asesmen_kode');
        });

        Schema::create('risiko_penanganan', function (Blueprint $table) {
            $table->foreignId('risiko_id')->constrained('risiko')->restrictOnDelete();
            $table->foreignId('rencana_penanganan_id')->constrained('rencana_penanganan')->restrictOnDelete();
            $table->primary(['risiko_id', 'rencana_penanganan_id']);
        });

        Schema::create('progres_penanganan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rencana_penanganan_id')->constrained('rencana_penanganan')->restrictOnDelete();
            $table->foreignId('dilaporkan_oleh')->constrained('users')->restrictOnDelete();
            $table->text('uraian');
            $table->text('hasil');
            $table->text('kendala');
            $table->string('status', 50);
            $table->dateTime('dilaporkan_pada');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('progres_penanganan');
        Schema::dropIfExists('risiko_penanganan');
        Schema::dropIfExists('rencana_penanganan');
        Schema::dropIfExists('risiko_rencana_kerja');
        Schema::dropIfExists('rencana_kerja');
        Schema::dropIfExists('review_asesmen');
        Schema::dropIfExists('keputusan_risiko');
        Schema::dropIfExists('usulan_keputusan');
    }
};
