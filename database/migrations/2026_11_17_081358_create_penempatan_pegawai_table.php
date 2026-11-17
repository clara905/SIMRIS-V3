
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('penempatan_pegawai', function (Blueprint $table) {
            $table->id();

            $table->foreignId('pegawai_id')
                ->constrained('pegawai')
                ->restrictOnDelete();

            $table->foreignId('unit_id')
                ->constrained('unit_organisasi')
                ->restrictOnDelete();

            $table->date('mulai');
            $table->date('selesai')->nullable();

            $table->index(['pegawai_id', 'selesai']);
            $table->index(['unit_id', 'selesai']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('penempatan_pegawai');
    }
};
