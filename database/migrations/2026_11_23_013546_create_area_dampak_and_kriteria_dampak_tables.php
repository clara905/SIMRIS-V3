<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('area_dampak', function (Blueprint $table) {
            $table->id();
            $table->string('kode', 100)->unique();
            $table->string('nama', 255);
            $table->text('deskripsi');
        });

        Schema::create('kriteria_dampak', function (Blueprint $table) {
            $table->id();
            $table->foreignId('skala_dampak_id')
                ->constrained('skala_dampak')
                ->restrictOnDelete();
            $table->foreignId('area_dampak_id')
                ->constrained('area_dampak')
                ->restrictOnDelete();
            $table->string('arah_dampak', 50);
            $table->text('deskripsi');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kriteria_dampak');
        Schema::dropIfExists('area_dampak');
    }
};
