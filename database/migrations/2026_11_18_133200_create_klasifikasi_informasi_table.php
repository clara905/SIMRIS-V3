<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('klasifikasi_informasi', function (Blueprint $table) {
            $table->id();
            $table->string('kode', 100)->unique();
            $table->string('nama', 255);
            $table->text('deskripsi');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('klasifikasi_informasi');
    }
};
