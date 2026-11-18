<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('log_audit', function (Blueprint $table) {
            $table->string('pelaku_nama', 255)
                ->nullable()
                ->after('user_id');

            $table->string('pelaku_username', 100)
                ->nullable()
                ->after('pelaku_nama');
        });
    }

    public function down(): void
    {
        Schema::table('log_audit', function (Blueprint $table) {
            $table->dropColumn([
                'pelaku_nama',
                'pelaku_username',
            ]);
        });
    }
};
