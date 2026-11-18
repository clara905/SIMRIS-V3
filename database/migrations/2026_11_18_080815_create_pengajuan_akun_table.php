<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pengajuan_akun', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->constrained('users')
                ->restrictOnDelete();

            $table->string('status', 20)->default('MENUNGGU');

            $table->text('catatan')->nullable();

            $table->foreignId('ditinjau_oleh')
                ->nullable()
                ->constrained('users')
                ->restrictOnDelete();

            $table->dateTime('diajukan_pada');

            $table->dateTime('ditinjau_pada')->nullable();

            $table->index(
                ['user_id', 'status'],
                'pengajuan_akun_user_status_index'
            );

            $table->index(
                ['status', 'diajukan_pada'],
                'pengajuan_akun_status_diajukan_index'
            );

            $table->index('ditinjau_oleh');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pengajuan_akun');
    }
};
