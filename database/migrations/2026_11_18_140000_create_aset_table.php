
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('aset', function (Blueprint $table) {
            $table->bigIncrements('idx');

            $table->foreignId('sumber_id')
                ->constrained('sumber_aset')
                ->restrictOnDelete();

            $table->string('external_id')->nullable();

            $table->foreignId('unit_pemilik_id')
                ->constrained('unit_organisasi')
                ->restrictOnDelete();

            $table->foreignId('kategori_id')
                ->constrained('kategori_aset')
                ->restrictOnDelete();

            $table->foreignId('klasifikasi_informasi_id')
                ->nullable()
                ->constrained('klasifikasi_informasi')
                ->restrictOnDelete();

            $table->string('kode_aset')->unique();
            $table->string('nama');
            $table->text('deskripsi');
            $table->string('merk', 150);
            $table->string('nomor_seri')->nullable();
            $table->integer('jumlah');
            $table->string('kondisi', 50);
            $table->string('lokasi');
            $table->date('tanggal_pengadaan');
            $table->string('status', 50);

            $table->dateTime('last_synced_at')->nullable();
            $table->dateTime('dibuat_pada');
            $table->dateTime('diperbarui_pada');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('aset');
    }
};
