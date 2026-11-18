<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('log_audit', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users')
                ->restrictOnDelete();

            $table->string('aksi', 100);
            $table->string('entitas', 100);
            $table->unsignedBigInteger('entitas_id')->nullable();

            $table->json('data_lama')->nullable();
            $table->json('data_baru')->nullable();

            $table->string('ip_address', 45)->nullable();

            $table->foreignId('koreksi_dari_id')
                ->nullable()
                ->constrained('log_audit')
                ->restrictOnDelete();

            $table->text('catatan')->nullable();

            $table->dateTime('waktu');

            $table->index(['user_id', 'waktu']);
            $table->index(['entitas', 'entitas_id']);
            $table->index('koreksi_dari_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('log_audit');
    }
};
