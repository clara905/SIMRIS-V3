<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_role_scope', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->constrained('users')
                ->restrictOnDelete();

            $table->foreignId('role_id')
                ->constrained('roles')
                ->restrictOnDelete();

            $table->foreignId('unit_id')
                ->nullable()
                ->constrained('unit_organisasi')
                ->restrictOnDelete();

            $table->dateTime('mulai');
            $table->dateTime('selesai')->nullable();

            $table->index(['user_id', 'selesai']);
            $table->index(['role_id', 'unit_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_role_scope');
    }
};
