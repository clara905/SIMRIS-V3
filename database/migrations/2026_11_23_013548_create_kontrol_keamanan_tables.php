<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('standar_kontrol', function (Blueprint $table) {
            $table->id();
            $table->string('kode', 100)->unique();
            $table->string('nama', 255);
            $table->string('versi', 100);
            $table->text('referensi');
        });

        Schema::create('kontrol_keamanan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('standar_kontrol_id')->constrained('standar_kontrol')->restrictOnDelete();
            $table->foreignId('unit_pemilik_id')->constrained('unit_organisasi')->restrictOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('kontrol_keamanan')->restrictOnDelete();
            $table->string('kode', 100);
            $table->string('nama', 255);
            $table->string('jenis', 100);
            $table->text('deskripsi');
            $table->boolean('aktif')->default(true);
            $table->foreignId('dibuat_oleh')->constrained('users')->restrictOnDelete();
            $table->dateTime('dibuat_pada');
            $table->dateTime('diperbarui_pada');
            $table->unique(['standar_kontrol_id', 'kode'], 'uq_kontrol_keamanan_standar_kode');
        });

        Schema::create('risiko_kontrol', function (Blueprint $table) {
            $table->foreignId('risiko_id')->constrained('risiko')->restrictOnDelete();
            $table->foreignId('kontrol_id')->constrained('kontrol_keamanan')->restrictOnDelete();
            $table->string('kode_snapshot', 100);
            $table->string('nama_snapshot', 255);
            $table->text('deskripsi_snapshot');
            $table->text('uraian_penerapan');
            $table->primary(['risiko_id', 'kontrol_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('risiko_kontrol');
        Schema::dropIfExists('kontrol_keamanan');
        Schema::dropIfExists('standar_kontrol');
    }
};
