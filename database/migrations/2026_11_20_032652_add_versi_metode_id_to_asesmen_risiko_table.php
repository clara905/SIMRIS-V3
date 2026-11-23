
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('asesmen_risiko', function (Blueprint $table) {
            $table->foreignId('versi_metode_id')
                ->nullable()
                ->after('unit_id')
                ->constrained('versi_metode_risiko')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('asesmen_risiko', function (Blueprint $table) {
            $table->dropForeign(['versi_metode_id']);
            $table->dropColumn('versi_metode_id');
        });
    }
};
