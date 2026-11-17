
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pegawai', function (Blueprint $table) {
            $table->id();
            $table->string('nip', 50)->unique();
            $table->string('nama', 255);
            $table->string('email_kontak', 255)->nullable();
            $table->string('jenis_kepegawaian', 20);
            $table->boolean('aktif')->default(true);
        });

        if (in_array(DB::getDriverName(), ['mysql', 'mariadb', 'pgsql'], true)) {
            DB::statement("
                ALTER TABLE pegawai
                ADD CONSTRAINT chk_pegawai_jenis_kepegawaian
                CHECK (jenis_kepegawaian IN (
                    'PNS',
                    'PPPK',
                    'NON_PNS',
                    'TNI'
                ))
            ");
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('pegawai');
    }
};
