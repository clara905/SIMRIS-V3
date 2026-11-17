<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('unit_organisasi', function (Blueprint $table) {
            $table->id();

            $table->foreignId('parent_id')
                ->nullable()
                ->constrained('unit_organisasi')
                ->restrictOnDelete();

            $table->string('kode', 100)->unique();
            $table->string('nama', 255);
            $table->string('jenis', 20);
            $table->boolean('aktif')->default(true);
        });

        if (in_array(DB::getDriverName(), ['mysql', 'mariadb', 'pgsql'], true)) {
            DB::statement("
                ALTER TABLE unit_organisasi
                ADD CONSTRAINT chk_unit_organisasi_jenis
                CHECK (jenis IN (
                    'SATKER',
                    'BIDANG',
                    'BAGIAN',
                    'SUBBIDANG',
                    'SUBBAGIAN'
                ))
            ");
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('unit_organisasi');
    }
};
