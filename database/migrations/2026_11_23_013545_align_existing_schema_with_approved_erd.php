<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreign('pegawai_id')
                ->references('id')
                ->on('pegawai')
                ->restrictOnDelete();
        });

        Schema::table('aset', function (Blueprint $table) {
            $table->foreignId('klasifikasi_informasi_id')->nullable(false)->change();
        });

        Schema::table('asesmen_risiko', function (Blueprint $table) {
            $table->foreignId('versi_metode_id')->nullable(false)->change();
        });

        Schema::table('skala_kemungkinan', function (Blueprint $table) {
            $table->unique(['id', 'versi_metode_id'], 'uq_skala_kemungkinan_id_versi');
        });

        Schema::table('skala_dampak', function (Blueprint $table) {
            $table->unique(['id', 'versi_metode_id'], 'uq_skala_dampak_id_versi');
        });

        Schema::table('tingkat_risiko', function (Blueprint $table) {
            $table->unique(['id', 'versi_metode_id'], 'uq_tingkat_risiko_id_versi');
        });

        Schema::table('matriks_risiko', function (Blueprint $table) {
            $table->foreign(['kemungkinan_id', 'versi_metode_id'], 'fk_matriks_kemungkinan_versi')
                ->references(['id', 'versi_metode_id'])
                ->on('skala_kemungkinan')
                ->restrictOnDelete();
            $table->foreign(['dampak_id', 'versi_metode_id'], 'fk_matriks_dampak_versi')
                ->references(['id', 'versi_metode_id'])
                ->on('skala_dampak')
                ->restrictOnDelete();
            $table->foreign(['tingkat_risiko_id', 'versi_metode_id'], 'fk_matriks_tingkat_versi')
                ->references(['id', 'versi_metode_id'])
                ->on('tingkat_risiko')
                ->restrictOnDelete();
        });

        Schema::table('sumber_ancaman', function (Blueprint $table) {
            $table->text('deskripsi')->nullable();
        });

        Schema::table('jenis_ancaman', function (Blueprint $table) {
            $table->text('deskripsi')->nullable();
        });

        Schema::table('jenis_kerentanan', function (Blueprint $table) {
            $table->text('deskripsi')->nullable();
        });

        Schema::table('ancaman', function (Blueprint $table) {
            $table->text('deskripsi')->nullable(false)->change();
        });

        Schema::table('kerentanan', function (Blueprint $table) {
            $table->text('deskripsi')->nullable(false)->change();
        });

        Schema::table('risiko', function (Blueprint $table) {
            $table->text('uraian_pelaksanaan_rencana_kerja')->nullable();
            $table->text('uraian_penerapan_sop_kontrol')->nullable();
        });

        Schema::table('risiko_aset', function (Blueprint $table) {
            $table->unique(['risiko_id', 'aset_idx'], 'uq_risiko_aset_risiko_aset');
        });

        if (! in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            return;
        }

        DB::statement("
            ALTER TABLE sumber_ancaman
            ADD CONSTRAINT chk_sumber_ancaman_kode
            CHECK (kode IN ('S', 'TS', 'L'))
        ");

        DB::unprepared(<<<'SQL'
            CREATE TRIGGER trg_user_role_scope_unit_before_insert
            BEFORE INSERT ON user_role_scope
            FOR EACH ROW
            BEGIN
                DECLARE role_kode VARCHAR(50);

                SELECT kode INTO role_kode
                FROM roles
                WHERE id = NEW.role_id;

                IF role_kode <> 'SUPER_ADMIN' AND NEW.unit_id IS NULL THEN
                    SIGNAL SQLSTATE '45000'
                    SET MESSAGE_TEXT = 'Scope unit wajib untuk role selain SUPER_ADMIN.';
                END IF;
            END
        SQL);

        DB::unprepared(<<<'SQL'
            CREATE TRIGGER trg_user_role_scope_unit_before_update
            BEFORE UPDATE ON user_role_scope
            FOR EACH ROW
            BEGIN
                DECLARE role_kode VARCHAR(50);

                SELECT kode INTO role_kode
                FROM roles
                WHERE id = NEW.role_id;

                IF role_kode <> 'SUPER_ADMIN' AND NEW.unit_id IS NULL THEN
                    SIGNAL SQLSTATE '45000'
                    SET MESSAGE_TEXT = 'Scope unit wajib untuk role selain SUPER_ADMIN.';
                END IF;
            END
        SQL);
    }

    public function down(): void
    {
        // This migration intentionally has no rollback because its constraints protect existing data.
    }
};
